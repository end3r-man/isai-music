<?php

namespace App\Commands;

use App\Models\Guild;
use App\Models\UserQueue;
use Discord\Builders\Components\Button;
use Discord\Parts\Interactions\Interaction;
use Laracord\Commands\Command;

class PingCommand extends Command
{
    /**
     * The command name.
     *
     * @var string
     */
    protected $name = 'ping';

    /**
     * The command description.
     *
     * @var string|null
     */
    protected $description = 'Ping? Pong!';

    /**
     * Handle the command.
     *
     * @param  \Discord\Parts\Channel\Message  $message
     * @param  array  $args
     * @return void
     */
    public function handle($message, $args)
    {
        $guilds = Guild::all();

        foreach ($guilds as $key => $value) {
            $vc = $this->discord()->getVoiceClient($value->guild_id);
            $list = UserQueue::where('guild_id', $value->id)->first();

            $song = json_decode($list->queue);

            dd($song[0]);
        }
    }
}
