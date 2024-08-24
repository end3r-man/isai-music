<?php

namespace App\Commands;

use App\Models\Guild;
use App\Models\UserQueue;
use Discord\Parts\Interactions\Interaction;
use Discord\Voice\VoiceClient;
use Illuminate\Support\Facades\Http;
use Laracord\Commands\Command;
use React\Promise\PromiseInterface;

use function React\Async\await;

class Play extends Command
{
    /**
     * The command name.
     *
     * @var string
     */
    protected $name = 'play';

    /**
     * The command description.
     *
     * @var string
     */
    protected $description = 'To Play.';

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
        $search = $args[0] . " " . ($args[1] ?? null);

        if ($message->member->getVoiceChannel()) {
            $response = Http::retry(3, 100)->withQueryParameters([
                'limit' => '1',
                'page' => '0',
                'query' => $search,
            ])->get('https://saavn.dev/api/search/songs');

            $this->HandlePlay($response, $message);
        } else {
            $this
                ->message('🚫 You must be in a channel to use that!')
                ->reply($message);
        }
    }

    /**
     * The command interaction routes.
     */
    public function interactions(): array
    {
        return [
            'wave' => fn(Interaction $interaction) => $this->message('👋')->reply($interaction),
        ];
    }

    /**
     * HandlePlay
     *
     * @param  interactions $interaction
     * @return discord message
     */
    protected function HandlePlay($song, $message): void
    {
        $data = json_decode($song)->data->results[0];

        $url = $data->downloadUrl[4]->url;

        if ($this->discord()->getVoiceClient($message->guild_id)) {
            $guild = $this->HandleGuild($message->guild_id, $message->guild->owner_id);

            $this->HandleQueue($guild, $data, $message);
        } else {
            $this->discord()->joinVoiceChannel($message->member->getVoiceChannel())->done(function (VoiceClient $vc) use ($url, $message) {
                $vc->setBitrate(320000);
                $vc->playFile($url);

                $data = $this->HandleGuild($message->guild_id, $message->guild->owner_id);
                $queue = UserQueue::where('guild_id', $data->id)->first();

                if (!is_null($queue)) {
                    $queue->delete();
                }
            });

            $this
                ->message("🎶 Start Playing ``{$data->name}``")
                ->reply($message, ephemeral: true);
        }
    }


    protected function HandleGuild($guild, $owner)
    {
        $data = Guild::where('guild_id', $guild)->first();

        if (!$data) {
            $data = Guild::create([
                'guild_id' => $guild,
                'owner_id' => $owner,
            ]);
        }

        return $data;
    }

    protected function HandleQueue($guild, $data, $interaction)
    {
        $songs = UserQueue::where('guild_id', $guild->id)->first();
        $url = $data->downloadUrl[4]->url;

        if ($songs) {
            $list = json_decode($songs->queue, true); // Decode the existing queue
            array_push($list, $url); // Add the new URL to the list
            $songs->queue = json_encode($list); // Encode the updated list back to JSON
            $songs->save(); // Save the changes to the database
        } else {
            UserQueue::create([
                'guild_id' => $guild->id,
                'queue' => json_encode([$url]), // Create a new queue with the first URL
            ]);
        }

        $this
            ->message("🎶 Added To Queue ``{$data->name}``")
            ->reply($interaction, ephemeral: true);
    }
}
