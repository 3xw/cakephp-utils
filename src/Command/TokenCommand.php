<?php
declare(strict_types=1);

namespace Trois\Utils\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Trois\Utils\Utility\Crypto\JWT;

/**
* Token command.
*/
class TokenCommand extends Command
{
  public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
  {
    $parser = parent::buildOptionParser($parser);

    return $parser;
  }

  /**
  * Implement this method with your command's logic.
  *
  * @param \Cake\Console\Arguments $args The command arguments.
  * @param \Cake\Console\ConsoleIo $io The console io
  * @return null|void|int The exit code or null for success
  */
  public function execute(Arguments $args, ConsoleIo $io)
  {
    if(!$username = $args->getArgumentAt(0)) throw new \Exception('Please provide a username');

    debug($username);
    $this->loadModel('Users');

    if(!$user = $this->Users->find()->where(['Users.username' => $username])->first()) throw new \Exception('no user found');

    $io->info(JWT::encode(['sub' => $user->id, 'exp' => time() + (3 * 30 * 24 * 60 * 60)]));
  }
}
