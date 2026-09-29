<?php
namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class TestAuthorization extends Command
{
    protected $signature = 'test:authorization';

    protected $description = 'Test user roles and permissions';

    public function handle()
    {
        $user = User::find(1);

        if (!$user) {
            $this->error('User with ID 1 was not found.');
            return Command::FAILURE;
        }

        $this->info('User: ' . $user->name);
        $this->info('Email: ' . $user->email);

        $this->info(
            'Has Super Administrator role: ' .
            ($user->hasRole('Super Administrator') ? 'YES' : 'NO')
        );

        $this->info(
            'Has students.delete permission: ' .
            ($user->hasPermission('students.delete') ? 'YES' : 'NO')
        );

        return Command::SUCCESS;
    }
}
    
