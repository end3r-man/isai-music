<?php

namespace App\Commands;

use Discord\Parts\Interactions\Interaction;
use Laracord\Commands\Command;

class Volume extends Command
{
    /**
     * The command name.
     *
     * @var string
     */
    protected $name = 'volume';

    /**
     * The command description.
     *
     * @var string
     */
    protected $description = 'Change Volume';

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
        $vc = $this->discord()->getVoiceClient($message->guild_id);

        if ($vc->isSpeaking() && is_int($args[1])) {
            $vc->setVolume($args[1]);
        }
    }

    /**
     * The command interaction routes.
     */
    public function interactions(): array
    {
        return [
            'wave' => fn (Interaction $interaction) => $this->message('👋')->reply($interaction), 
        ];
    }
}
