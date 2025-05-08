<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:test {email : O email para enviar o teste}';
    protected $description = 'Envia um email de teste para verificar a configuração de email';

    
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $this->info("Tentando enviar email para: $email");

        try {
            Mail::raw('Teste de envio de email do Laravel ' . now(), function($message) use ($email) {
                $message->to($email)
                        ->subject('Teste de Email');
            });

            $this->info('Email enviado com sucesso!');
        } catch (\Exception $e) {
            $this->error('Erro ao enviar email: ' . $e->getMessage());
        }

        return Command::SUCCESS;
    }
}
