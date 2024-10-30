<?php

namespace App\Console\Commands;

use App\Exceptions\FunnelDeployException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Log;
use Storage;
use Symfony\Component\Process\Process;

class DeployFunnel extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deploy:funnel {page}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deploy a product funnel landing page with NGINX and request SSL certification using certbot';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $page = $this->argument('page');
        $root_path = "/var/www/funnels/{$page}";
        $sub_domain = "{$page}.trybytealley.com";
        $email = 'info@trybytealley.com';

        $this->getCurrentUser();

        // Step 1: Copy HTML file to destination directory
        $this->copyHtmlToDestinationDir($page, $root_path);

        // Step 2: Generate NGINX configuration file
        $nginx_config = $this->generateNginxConfig($sub_domain, $root_path);

        // Step 3: Copy the config file into nginx directory
        $this->writeNginxConfig($nginx_config, $page);

        // Step 4: Create symlink for NGINX
        $this->createNginxSymlink($page);

        // Step 5: Reload NGINX
        $this->reloadNginx();

        // Step 6: Use Digital Ocean API to register a subdomain for the funnel
        $this->createDigitalOceanSubdomain($page);

        // Step 7: Certify SSL using certbot
        $this->certifyWithCertbot($sub_domain, $email);

        $this->info("Deployment for {$sub_domain} completed successfully!");
    }

    protected function getCurrentUser()
    {
        $process = new Process(['whoami']);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new FunnelDeployException('Failed to determine the current user: '.$process->getErrorOutput());
        }

        Log::channel('webhook')->debug('whoami result', ['context' => $process->getOutput()]);
    }

    public function copyHtmlToDestinationDir(string $page, string $root_path)
    {

        // Path to the source file within the Laravel project directory
        $sourcePath = storage_path("app/funnels/{$page}.html");

        $destinationPath = "{$root_path}/index.html";

        if (! file_exists($sourcePath)) {
            throw new FunnelDeployException("Funnel HTML page not found at {$sourcePath}");
        }

        // Ensure the destination directory exists
        $makeDirCommand = new Process(['sudo', 'mkdir', '-p', $root_path]);

        $makeDirCommand->run();

        if (! $makeDirCommand->isSuccessful()) {
            throw new FunnelDeployException("Failed to create destination directory: {$makeDirCommand->getErrorOutput()}");
        }

        // Copy the file with sudo cp
        $copyCommand = new Process(['sudo', 'cp', $sourcePath, $destinationPath]);

        $copyCommand->run();

        if (! $copyCommand->isSuccessful()) {
            throw new FunnelDeployException("Failed to copy HTML page: {$copyCommand->getErrorOutput()}");
        }

        $this->info("Funnel HTML page copied to {$destinationPath}");
    }

    /**
     * Generate NGINX config from stub.
     *
     * @return string
     */
    protected function generateNginxConfig(string $server_name, string $root_path)
    {
        $stub = $this->getStub();

        return str_replace(
            ['{{server_name}}', '{{root_path}}'],
            [$server_name, $root_path],
            $stub
        );
    }

    protected function writeNginxConfig(string $config_content, string $file_name)
    {
        // Prepare the command to write the config file with sudo privileges
        $command = "echo '$config_content' | sudo tee /etc/nginx/sites-available/{$file_name}.conf";

        $process = Process::fromShellCommandline($command);
        $process->run();

        // Check if the command was successful
        if (! $process->isSuccessful()) {
            throw new FunnelDeployException('Failed to write NGINX config: '.$process->getErrorOutput());
        }

        $this->info("NGINX config created for {$file_name}");
    }

    /**
     * Create a symbolic link in NGINX's sites-enabled directory.
     *
     * @param  string  $file_name
     * @return void
     *
     * @throws FunnelDeployException
     */
    protected function createNginxSymlink($file_name)
    {
        $command = "sudo ln -s /etc/nginx/sites-available/{$file_name}.conf /etc/nginx/sites-enabled/{$file_name}.conf";
        $process = Process::fromShellCommandline($command);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new FunnelDeployException('Failed to create symlink: '.$process->getErrorOutput());
        }

        $this->info("Symlink created for {$file_name} in sites-enabled");
    }

    protected function reloadNginx()
    {
        $processReload = new Process(['sudo', 'systemctl', 'reload', 'nginx']);
        $processReload->run();

        $this->checkProcess($processReload, 'NGINX reloaded');
    }

    /**
     * Get the stub file for the nginx configuration
     *
     * @return string
     */
    protected function getStub()
    {
        return <<<'STUB'
            server {
                listen 80;
                server_name {{server_name}};
                root {{root_path}};

                location / {
                try_files $uri $uri/ =404;
                }
            }
            STUB;
    }

    protected function createDigitalOceanSubdomain($sub_domain)
    {
        $payload = [
            'type' => 'A',
            'name' => $sub_domain,
            'data' => config('services.digitalocean.ip'),
            'priority' => null,
            'port' => null,
            'ttl' => 1800,
            'weight' => null,
            'flags' => null,
            'tags' => null,
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.config('services.digitalocean.token'),
            'Content-Type' => 'application/json',
        ])->post('https://api.digitalocean.com/v2/domains/trybytealley.com/records', $payload);

        if (! $response->successful()) {
            throw new FunnelDeployException('Failed to create subdomain in DigitalOcean>>>>>>>>>>>>>>>>>>>>'.$response->reason());
        }

        $this->info("Subdomain {$sub_domain} created in DigitalOcean");
    }

    protected function checkProcess(Process $process, $message)
    {
        if (! $process->isSuccessful()) {
            throw new FunnelDeployException($process->getErrorOutput());
        }

        $this->info($message);
    }

    public function certifyWithCertbot(string $sub_domain, string $email)
    {
        $processCertbot = new Process(['sudo', 'certbot', '--nginx', '-d', $sub_domain, '--non-interactive', '--agree-tos', '-m', $email]);
        $processCertbot->run();

        $this->checkProcess($processCertbot, 'SSL certificate applied');
    }
}
