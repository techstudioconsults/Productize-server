<?php

namespace App\Console\Commands;

use App\Exceptions\FunnelDeployException;
use App\Models\Funnel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Log;
use Symfony\Component\Process\Process;

/**
 * Class DeployFunnel
 *
 * Command for deploying a funnel landing page.
 * This command handles copying HTML templates, configuring NGINX,
 * creating subdomains on DigitalOcean, and applying SSL certificates using Certbot.
 *
 * Example Usage:
 * php artisan deploy:funnel {page}
 *
 * or from your code:
 * Artisan::call('deploy:funnel', ['page' => $page_name]);
 *
 * NOTE: ONLY RUN IN A UNIX SERVER
 *
 * @version 1.0
 *
 * @since 30-10-2024
 */
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
    protected $description = 'Deploy a funnel landing page with NGINX and request SSL certification using certbot';

    /**
     * Execute the console command.
     *
     * @throws FunnelDeployException
     */
    public function handle()
    {
        $page = $this->argument('page');
        $root_path = "/var/www/funnels/{$page}";
        $sub_domain = "{$page}.trybytealley.com";
        $email = 'info@trybytealley.com';

        // Step 1: Copy HTML file to destination directory
        $this->moveFunnelToDestinationDir($page, $root_path);

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

    /**
     * Logs the current system user running the command.
     *
     * @return void
     *
     * @throws FunnelDeployException
     */
    protected function getCurrentUser()
    {
        $process = new Process(['whoami']);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new FunnelDeployException('Failed to determine the current user: ' . $process->getErrorOutput());
        }

        Log::channel('webhook')->debug('whoami result', ['context' => $process->getOutput()]);
    }

    /**
     * moves the Funnel pages to the specified NGINX root directory.
     *
     * @param  string  $page  The name of the funnel page
     * @param  string  $root_path  The root path for the page
     *
     * @throws FunnelDeployException
     */
    public function moveFunnelToDestinationDir(string $page, string $root_path)
    {
        // Path to the source directory within the Laravel project directory
        $sourcePath = storage_path("app/funnels/{$page}");

        if (! is_dir($sourcePath)) {
            throw new FunnelDeployException("Funnel directory not found at {$sourcePath}");
        }

        // Move the entire directory with sudo mv
        $moveCommand = new Process(['sudo', 'cp', '-r', $sourcePath, '/var/www/funnels/']);
        $moveCommand->run();

        if (! $moveCommand->isSuccessful()) {
            throw new FunnelDeployException("Failed to move funnel files: {$moveCommand->getErrorOutput()}");
        }

        // Run chown to change ownership
        $chownCommand = new Process(['sudo', 'chown', '-R', 'www-data:www-data', $root_path]);
        $chownCommand->run();

        if (! $chownCommand->isSuccessful()) {
            throw new FunnelDeployException("Failed to change ownership of files: {$chownCommand->getErrorOutput()}");
        }

        // Remove the empty source directory
        $removeCommand = new Process(['sudo', 'rmdir', $sourcePath]);
        $removeCommand->run();

        if (! $removeCommand->isSuccessful()) {
            $this->warn("Note: Could not remove empty source directory: {$removeCommand->getErrorOutput()}");
        }

        $this->info("Funnel files moved to {$root_path}");
    }

    /**
     * Generates the NGINX configuration based on a stub template.
     *
     * @param  string  $server_name  The server name (subdomain) for the configuration
     * @param  string  $root_path  The root path for the server
     * @return string The generated NGINX configuration
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

    /**
     * Writes the generated NGINX configuration to the sites-available directory.
     *
     * @param  string  $config_content  The NGINX configuration content
     * @param  string  $file_name  The filename for the NGINX configuration
     *
     * @throws FunnelDeployException
     */
    protected function writeNginxConfig(string $config_content, string $file_name)
    {
        // Prepare the command to write the config file with sudo privileges
        $command = "echo '$config_content' | sudo tee /etc/nginx/sites-available/{$file_name}.conf";

        $process = Process::fromShellCommandline($command);
        $process->run();

        // Check if the command was successful
        if (! $process->isSuccessful()) {
            throw new FunnelDeployException('Failed to write NGINX config: ' . $process->getErrorOutput());
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
        $command = "sudo ln -sf /etc/nginx/sites-available/{$file_name}.conf /etc/nginx/sites-enabled/{$file_name}.conf";
        $process = Process::fromShellCommandline($command);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new FunnelDeployException('Failed to create symlink: ' . $process->getErrorOutput());
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

                index index.html;

                location / {
                try_files $uri $uri/ =404;
                }
            }
            STUB;
    }

    /**
     * Creates a subdomain record in DigitalOcean DNS for the funnel page.
     *
     * @param  string  $sub_domain  The subdomain name to create
     *
     * @throws FunnelDeployException
     */
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
            'Authorization' => 'Bearer ' . config('services.digitalocean.token'),
            'Content-Type' => 'application/json',
        ])->post('https://api.digitalocean.com/v2/domains/trybytealley.com/records', $payload);

        if (! $response->successful()) {
            throw new FunnelDeployException('Failed to create subdomain in DigitalOcean' . $response->reason());
        }

        // save domainId in db
        $funnel = Funnel::where('slug', $sub_domain)->update(['sub_domain_id' => $response['domain_record']['id']]);

        Log::channel('webhook')->debug('Funnel Update result', ['context' => $funnel]);

        $this->info("Subdomain {$sub_domain} created in DigitalOcean");
    }

    /**
     * Checks if the process executed successfully, and throws an exception if not.
     *
     * @param  Process  $process  The process to check
     * @param  string  $message  The success message to display
     *
     * @throws FunnelDeployException
     */
    protected function checkProcess(Process $process, $message)
    {
        if (! $process->isSuccessful()) {
            throw new FunnelDeployException($process->getErrorOutput());
        }

        $this->info($message);
    }

    /**
     * Requests an SSL certificate for the subdomain using Certbot.
     *
     * @param  string  $sub_domain  The subdomain to certify
     * @param  string  $email  The email for certbot notifications
     *
     * @throws FunnelDeployException
     */
    public function certifyWithCertbot(string $sub_domain, string $email)
    {
        $processCertbot = new Process(['sudo', 'certbot', '--nginx', '-d', $sub_domain, '--non-interactive', '--agree-tos', '-m', $email]);
        $processCertbot->run();

        $this->checkProcess($processCertbot, 'SSL certificate applied');
    }
}
