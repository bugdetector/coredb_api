<?php

namespace Src\Command;

use CoreDB;
use Exception;
use Src\Entity\Translation;
use Src\Entity\User;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AddAdminUserCommand extends Command
{
    protected static $defaultName = "user:add-admin";

    protected function configure()
    {
        $this->setDescription(
            Translation::getTranslation("add_admin")
        );
        $this->addArgument('username', InputArgument::REQUIRED, Translation::getTranslation("username"));
        $this->addArgument('email', InputArgument::REQUIRED, Translation::getTranslation("email"));
        $this->addArgument('name', InputArgument::REQUIRED, Translation::getTranslation("name"));
        $this->addArgument('password', InputArgument::REQUIRED, Translation::getTranslation("password"));
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $user = CoreDB::config()->getEntityInstance("users");
        $user->map([
            "username" => $input->getArgument("username"),
            "name" => $input->getArgument("name"),
            "email" => $input->getArgument("email"),
            "password" => $input->getArgument("password"),
            "status" => User::STATUS_ACTIVE,
            "roles" => [1]
        ]);
        try {
            $user->save();
            $output->writeln(Translation::getTranslation("insert_success"));
            return Command::SUCCESS;
        } catch (Exception $ex) {
            $output->writeln($ex->getMessage());
            return Command::FAILURE;
        }
    }
}
