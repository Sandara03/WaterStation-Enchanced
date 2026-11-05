<?php
namespace App\Console\Commands;

use App\Models\{AccessToken, User};
use App\Support\Audit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\{DB, Validator};

class CreateStaff extends Command
{
    protected $signature = 'staff:create {email} {--role=cashier} {--reset : Reset the password and role of an existing account}';
    protected $description = 'Create a staff account; passwords are entered privately';

    public function handle(): int
    {
        $email = strtolower(trim($this->argument('email')));
        $name = $this->ask('Full name');
        $password = $this->secret('Password (at least 12 characters)');
        $confirmation = $this->secret('Confirm password');
        $data = ['email' => $email, 'name' => $name, 'password' => $password, 'password_confirmation' => $confirmation, 'role' => $this->option('role')];
        $v = Validator::make($data, ['email' => 'required|email|max:255', 'name' => 'required|string|max:255', 'password' => 'required|string|min:12|max:128|confirmed', 'role' => 'required|in:admin,cashier']);
        if ($v->fails()) { foreach ($v->errors()->all() as $error) { $this->error($error); } return self::FAILURE; }
        $existing = User::where('email', $email)->first();
        if ($existing && !$this->option('reset')) { $this->error('Account exists. Use --reset to update it.'); return self::FAILURE; }
        DB::transaction(function () use ($email, $name, $password, $data) {
            $user = User::updateOrCreate(['email' => $email], ['name' => $name, 'password' => $password, 'role' => $data['role'], 'is_active' => true]);
            AccessToken::where('user_id', $user->id)->delete();
            Audit::record('staff.provisioned', 'User', $user->id, 'Staff account provisioned from console', ['role' => $user->role]);
        });
        $this->info('Staff account saved. Existing sessions were revoked.');
        return self::SUCCESS;
    }
}
