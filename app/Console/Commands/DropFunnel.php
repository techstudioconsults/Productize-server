<?php

namespace App\Console\Commands;

use App\Exceptions\FunnelDeployException;
use App\Models\Funnel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Process\Process;

class DropFunnel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'drop:funnel {page}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Drop a deployed funnel';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $page = $this->argument('page');
        $root_path = "/var/www/funnels/{$page}";
        $sub_domain = "{$page}.trybytealley.com";

        // delete nginx config and symlynk
        $this->deleteNginxConfig($page);

        // reload nginx
        $this->reloadNginx();

        // delete subdomain from digital ocean
        $this->deleteSubDomain($page);

        // undo certbot
        $this->deleteCertbotCertificate($sub_domain);

        // delete the funnel
        $this->deleteFunnel($root_path);
    }

    protected function deleteNginxConfig(string $file_name)
    {
        $command = "sudo rm /etc/nginx/sites-available/{$file_name}.conf /etc/nginx/sites-enabled/{$file_name}.conf";
        $process = Process::fromShellCommandline($command);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new FunnelDeployException('Failed to delete config and symlink: ' . $process->getErrorOutput());
        }

        $this->info("Symlink created for {$file_name} in sites-enabled");
    }

    /**
     * Reloads NGINX to apply new configuration changes.
     *
     * @throws FunnelDeployException
     */
    protected function reloadNginx()
    {
        $processReload = new Process(['sudo', 'systemctl', 'reload', 'nginx']);
        $processReload->run();

        $this->checkProcess($processReload, 'NGINX reloaded');
    }

    protected function deleteSubDomain(string $sub_domain)
    {
        $sub_domain_id = Funnel::where('slug', $sub_domain)->first()->sub_domain_id;

        if (!$sub_domain_id) {
            throw new FunnelDeployException('Failed to delete subdomain in DigitalOcean. subdomain not found');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.digitalocean.token'),
            'Content-Type' => 'application/json',
        ])->delete('https://api.digitalocean.com/v2/domains/trybytealley.com/records/' . $sub_domain_id);

        if (! $response->successful()) {
            throw new FunnelDeployException('Failed to delete subdomain in DigitalOcean' . $response->reason());
        }

        $this->info("Subdomain {$sub_domain} deleted in DigitalOcean");
    }

    protected function deleteCertbotCertificate(string $sub_domain)
    {
        $processCertbot = new Process(['sudo', 'certbot', 'delete', '--cert-name', $sub_domain]);
        $processCertbot->run();

        $this->checkProcess($processCertbot, 'SSL certificate deleted');
    }

    protected function deleteFunnel(string $root_path)
    {
        $command = "sudo rm -rf {$root_path}";
        $process = Process::fromShellCommandline($command);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new FunnelDeployException('Failed to delete funnel from root path: ' . $process->getErrorOutput());
        }

        $this->info("Funnel successfully deleted");
    }

    /**
     * Checks if the process executed successfully, and throws an exception if not.
     *
     * @param Process $process The process to check
     * @param string $message The success message to display
     * @throws FunnelDeployException
     */
    protected function checkProcess(Process $process, $message)
    {
        if (! $process->isSuccessful()) {
            throw new FunnelDeployException($process->getErrorOutput());
        }

        $this->info($message);
    }
}
