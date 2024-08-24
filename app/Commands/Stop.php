<?php

namespace App\Commands;

use Discord\Parts\Interactions\Interaction;
use Discord\Voice\VoiceClient;
use Laracord\Commands\Command;

class Stop extends Command
{
    /**
     * The command name.
     *
     * @var string
     */
    protected $name = 'stop';

    /**
     * The command description.
     *
     * @var string
     */
    protected $description = 'To Stop.';

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
        if ($message->member->getVoiceChannel()) {

            $chn = $this->discord()->getVoiceClient($message->guild_id);

            if ($chn) {
                $chn->close();
            }
        } else {
            $this
                ->message('🚫 You must be in a channel to use that!')
                ->reply($message);
        }
    }
}
