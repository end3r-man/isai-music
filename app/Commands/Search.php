<?php

namespace App\Commands;

use App\Models\Guild;
use App\Models\UserQueue;
use Discord\Http\Drivers\Guzzle;
use Discord\Parts\Interactions\Interaction;
use Discord\Voice\VoiceClient;
use Illuminate\Support\Facades\Http;
use Laracord\Commands\Command;

class Search extends Command
{
    /**
     * The command name.
     *
     * @var string
     */
    protected $name = 'search';

    /**
     * The command description.
     *
     * @var string
     */
    protected $description = 'To Search.';

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

        $search = implode(" ", $args);

        if ($message->member->getVoiceChannel()) {
            $response = Http::retry(3, 100)->withQueryParameters([
                'limit' => '5',
                'page' => '0',
                'query' => $search,
            ])->get('https://saavn.dev/api/search/songs');

            $songs = json_decode($response)->data->results;

            $list = $this->HandleSongsList($songs);

            $this
                ->message("Search result of ``{$search}``")
                ->select($list, route: "music", placeholder: "Select a song to play")
                ->reply($message);
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
            'music' => fn(Interaction $interaction) => $this->HandlePlay($interaction),
        ];
    }

    /**
     * HandleSongsList
     *
     * @param  array $songs
     * @return array
     */
    protected function HandleSongsList($songs): array
    {
        $list = [];

        foreach ($songs as $key => $song) {
            $list[$song->id] = [
                'label' => $song->name,
                'description' => "{$song->artists->primary[0]->name} - {$this->formatDuration($song->duration)}",
                'emoji' => '🎵',
            ];
        }

        return $list;
    }

    /**
     * formatDuration
     *
     * @param  mixed $duration
     * @return string
     */
    protected function formatDuration($duration): string
    {
        $minutes = floor($duration / 60);
        $seconds = floor($duration - ($minutes * 60));
        return sprintf('%d:%02d', $minutes, $seconds);
    }

    /**
     * HandlePlay
     *
     * @param  interactions $interaction
     * @return discord message
     */
    protected function HandlePlay(Interaction $interaction): void
    {

        $response = Http::get("https://saavn.dev/api/songs/{$interaction->data->values[0]}");

        if ($response) {
            $data = json_decode($response)->data[0];
            $url = $data->downloadUrl[4]->url;
            $owner = $this->discord->guilds->get('id', $interaction->guild_id)->owner_id;

            if ($this->discord()->getVoiceClient($interaction->guild_id)) {
                $guild = $this->HandleGuild($interaction->guild_id, $owner);

                $this->HandleQueue($guild, $data, $interaction);
            } else {

                $this->discord()->joinVoiceChannel($interaction->member->getVoiceChannel())->done(function (VoiceClient $vc) use ($url, $interaction, $owner) {
                    $vc->setBitrate(320000);
                    $vc->playFile($url);

                    $data = $this->HandleGuild($interaction->guild_id, $owner);
                    $queue = UserQueue::where('guild_id', $data->id)->first();

                    if (!is_null($queue)) {
                        $queue->delete();
                    }
                });

                $this
                    ->message("🎶 Start Playing ``{$data->name}``")
                    ->reply($interaction, ephemeral: true);
            }
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
