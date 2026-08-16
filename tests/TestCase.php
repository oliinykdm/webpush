<?php

namespace NotificationChannels\WebPush\Test;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Schema;
use NotificationChannels\WebPush\PushSubscription;
use NotificationChannels\WebPush\WebPushServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /** @var User */
    protected $testUser;

    protected Application $application;

    protected function setUp(): void
    {
        parent::setUp();

        $this->application = $this->app ?? throw new \RuntimeException('Test application is not available.');

        $this->setUpDatabase();

        $this->testUser = User::firstOrFail();
    }

    /**
     * Set up the environment.
     *
     * @param  Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('auth.providers.users.model', User::class);
    }

    /**
     * @param  Application  $app
     * @return array<array-key, class-string>
     */
    protected function getPackageProviders($app)
    {
        return [
            WebPushServiceProvider::class,
        ];
    }

    /**
     * @return void
     */
    protected function setUpDatabase()
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('email');
        });

        $migration = require __DIR__.'/../migrations/create_push_subscriptions_table.php.stub';
        $migration->up();

        $this->createUser(['email' => 'test@user.com']);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return User
     */
    public function createUser(array $attributes)
    {
        return User::create($attributes);
    }

    /**
     * @param  User  $user
     * @param  string  $endpoint
     * @return PushSubscription
     */
    public function createSubscription($user, $endpoint = 'endpoint')
    {
        return $user->pushSubscriptions()->create([
            'user_id' => $user->id,
            'endpoint' => $endpoint,
            'public_key' => 'key',
            'auth_token' => 'token',
        ]);
    }

    /**
     * @return void
     */
    protected function seeInConsoleOutput(string $expectedText)
    {
        $consoleOutput = $this->application->make(Kernel::class)->output();

        $this->assertStringContainsString($expectedText, $consoleOutput, sprintf('Did not see `%s` in console output: `%s`', $expectedText, $consoleOutput));
    }
}
