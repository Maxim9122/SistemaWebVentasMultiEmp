<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CrearSuperadmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'superadmin:crear {--name= : Nombre (si se omite, se pregunta)} {--email= : Email (si se omite, se pregunta)} {--password= : Password (si se omite, se pregunta)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crea un usuario superadmin de la plataforma (no pertenece a ninguna empresa)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $name = $this->option('name') ?? $this->ask('Nombre');
        $email = $this->option('email') ?? $this->ask('Email');
        $password = $this->option('password') ?? $this->secret('Password (mínimo 8 caracteres)');

        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $password],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', 'min:8'],
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        User::create([
            'empresa_id' => null,
            'role' => 'superadmin',
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ]);

        $this->info("Superadmin '{$email}' creado correctamente.");

        return self::SUCCESS;
    }
}
