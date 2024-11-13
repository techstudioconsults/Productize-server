<?php

namespace App\Console\Commands;

use App\Exceptions\DropFunnelException;
use App\Models\Funnel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Process\Process;

/**
 * Class DropFunnel
 *
 * Command for dropping a funnel landing page's deployment.
 * This command handles cdeleting the funnel's nginx and resp symlink config,
 * subdomain and certbot certification deletion and template removal from the server
 *
 * Example Usage:
 * php artisan drop:funnel {page}
 *
 * or from your code:
 * Artisan::call('deploy:funnel', ['page' => $funnel->slug]);
 *
 * NOTE: ONLY RUN IN A UNIX SERVER
 *
 * @version 1.0
 *
 * @since 13-11-2024
 */
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
     * Executes the console command to delete a funnel and its associated resources.
     *
     * This method performs the following actions in sequence:
     * - Deletes the Nginx configuration file and its symlink for the specified page.
     * - Reloads Nginx to apply configuration changes.
     * - Removes the subdomain associated with the page from DigitalOcean.
     * - Deletes the SSL certificate associated with the subdomain.
     * - Removes the funnel's directory and its contents from the server.
     *
     * @throws DropFunnelException if any of the operations fail, such as deleting the Nginx configuration,
     *         reloading Nginx, removing the subdomain, deleting the SSL certificate, or removing the funnel directory.
     *
     * @return void
     */
    public function handle()
    {
        $page = $this->argument('page');
        $root_path = "/var/www/funnels/{$page}";
        $sub_domain = "{$page}.trybytealley.com";

        // Delete Nginx configuration and symlink
        $this->deleteNginxConfig($page);

        // Reload Nginx to apply changes
        $this->reloadNginx();

        // Remove the subdomain from DigitalOcean
        $this->deleteSubDomain($page);

        // Reload Nginx again after subdomain deletion
        $this->reloadNginx();

        // Undo the SSL certificate configuration
        $this->deleteCertbotCertificate($sub_domain);

        // Final Nginx reload after certificate deletion
        $this->reloadNginx();

        // Delete the funnel directoryFFf
        $this->deleteFunnel($root_path);
    }

    /**
     * Deletes the specified Nginx configuration file and its symlink.
     *
     * This function uses the `rm` command with `sudo` to delete both the
     * Nginx configuration file from the sites-available directory and its symlink
     * from the sites-enabled directory. If the deletion is unsuccessful,
     * an exception is thrown with the relevant error output.
     *
     * @param string $file_name The base name of the Nginx configuration file
     *                          (without the `.conf` extension).
     *
     * @throws DropFunnelException if the deletion of the configuration file
     *                             or symlink fails.
     *
     * @return void
     */
    protected function deleteNginxConfig(string $file_name)
    {
        $command = "sudo rm /etc/nginx/sites-available/{$file_name}.conf /etc/nginx/sites-enabled/{$file_name}.conf";
        $process = Process::fromShellCommandline($command);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new DropFunnelException('Failed to delete config and symlink: ' . $process->getErrorOutput());
        }

        $this->info("config and symlynk deleted for {$file_name}");
    }

    /**
     * Reloads NGINX to apply new configuration changes.
     *
     * @throws DropFunnelException
     */
    protected function reloadNginx()
    {
        $processReload = new Process(['sudo', 'systemctl', 'reload', 'nginx']);
        $processReload->run();

        $this->checkProcess($processReload, 'NGINX reloaded');
    }

    /**
     * Deletes a specified subdomain from DigitalOcean's DNS records.
     *
     * This function uses the DigitalOcean API to delete a subdomain record
     * by its unique ID. It first retrieves the subdomain's ID from the
     * `Funnel` model, then performs an HTTP DELETE request to remove the
     * corresponding DNS record in DigitalOcean. If the subdomain ID is not found
     * or the API request fails, an exception is thrown.
     *
     * @param string $sub_domain The slug representing the subdomain to delete.
     *
     * @throws DropFunnelException if the subdomain ID is not found in the database
     *                             or if the DigitalOcean API request is unsuccessful.
     *
     * @return void
     */
    protected function deleteSubDomain(string $sub_domain)
    {
        $sub_domain_id = Funnel::where('slug', $sub_domain)->first()->sub_domain_id;

        if (! $sub_domain_id) {
            throw new DropFunnelException('Failed to delete subdomain in DigitalOcean. subdomain not found');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.digitalocean.token'),
            'Content-Type' => 'application/json',
        ])->delete('https://api.digitalocean.com/v2/domains/trybytealley.com/records/' . $sub_domain_id);

        if (! $response->successful()) {
            throw new DropFunnelException('Failed to delete subdomain in DigitalOcean' . $response->reason());
        }

        $this->info("Subdomain {$sub_domain} deleted in DigitalOcean");
    }

    /**
     * Deletes an SSL certificate for a specified subdomain using Certbot.
     *
     * This function invokes the Certbot command to delete the SSL certificate associated
     * with the given subdomain. It runs a process using `certbot delete` with the
     * `--cert-name` option set to the provided subdomain. If the process fails,
     * an exception is thrown by calling the `checkProcess` helper method.
     *
     * @param string $sub_domain The name of the subdomain whose SSL certificate should be deleted. e.g example.trybytealley.com
     *
     * @throws DropFunnelException if the Certbot command process fails.
     *
     * @return void
     */
    protected function deleteCertbotCertificate(string $sub_domain)
    {
        $processCertbot = new Process(['sudo', 'certbot', 'delete', '--cert-name', $sub_domain]);
        $processCertbot->run();

        $this->checkProcess($processCertbot, 'SSL certificate deleted');
    }

    /**
     * Deletes a specified directory from the server.
     *
     * This function removes the directory and all its contents from the file system
     * by executing a `sudo rm -rf` command targeting the specified root path. If the
     * deletion fails, an exception is thrown containing the error message.
     *
     * @param string $root_path The absolute path of the directory to be deleted.
     *
     * @throws DropFunnelException if the command to delete the directory fails.
     *
     * @return void
     */
    protected function deleteFunnel(string $root_path)
    {
        $command = "sudo rm -rf {$root_path}";
        $process = Process::fromShellCommandline($command);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new DropFunnelException('Failed to delete funnel from root path: ' . $process->getErrorOutput());
        }

        $this->info('Funnel successfully deleted');
    }

    /**
     * Checks if the process executed successfully, and throws an exception if not.
     *
     * @param  Process  $process  The process to check
     * @param  string  $message  The success message to display
     *
     * @throws DropFunnelException
     */
    protected function checkProcess(Process $process, $message)
    {
        if (! $process->isSuccessful()) {
            throw new DropFunnelException($process->getErrorOutput());
        }

        $this->info($message);
    }
}
