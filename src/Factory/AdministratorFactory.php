<?php

namespace App\Factory;

use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Faker\Factory as FakerFactory;


/**
 * @extends PersistentProxyObjectFactory<User>
 */
final class AdministratorFactory extends PersistentProxyObjectFactory
{
    private UserPasswordHasherInterface $passwordHasher;
    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        parent::__construct();
        $this->passwordHasher = $passwordHasher;
    }

    public static function class(): string
    {
        return User::class;
    }

    protected function defaults(): array|callable
    {
        return [
            'email' => "dev.souillat@gmail.com",
            'password' => "password",
            'postalCode' => '19410',
            'roles' => ['ROLE_USER', 'ROLE_ADMIN'],
        ];
    }

    protected function initialize(): static
    {
        return $this->afterInstantiate(function(User $user) {
            $user->setPassword(
                $this->passwordHasher->hashPassword($user, $user->getPassword())
            );
        });
    }
}
