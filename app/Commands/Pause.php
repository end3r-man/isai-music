<?php

namespace App\Commands;

use Discord\Parts\Interactions\Interaction;
use Laracord\Commands\Command;

class Pause extends Command
{
    /**
     * The command name.
     *
     * @var string
     */
    protected $name = 'pause';

    /**
     * The command description.
     *
     * @var string
     */
    protected $description = 'To Pause.';

    /**
     * Determines whether the command requires admin permissions.
     *
     * @var bool
     */
    protected $admin = false;

    /**
     * Determines whether the command should be displayed in the commands list.
     *
     * @var bool
     */
    protected $hidden = false;

    /**
     * Handle the command.
     *
     * @param  \Discord\Parts\Channel\Message  $message
     * @param  array  $args
     * @return void
     */
    public function handle($message, $args)
    {
        $client = $this->discord()->getVoiceClient($message->guild_id);

        if ($client !== null) {
            $client->isPaused() ? $client->unpause() : $client->pause();
        }
    }
}
