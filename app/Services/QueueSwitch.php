<?php

namespace App\Services;

use App\Models\Guild;
use App\Models\UserQueue;
use Discord\Voice\VoiceClient;
use Laracord\Services\Service;

class QueueSwitch extends Service
{
    /**
     * The service interval.
     */
    protected int $interval = 5;

    /**
     * Handle the service.
     */
    /**
     * Manage the voice clients and song queues for all guilds.
     *
     * @return void
     */
    public function handle(): void
    {
        $guilds = Guild::all();

        foreach ($guilds as $guild) {
            $this->handleGuildVoiceClient($guild);
        }
    }

    /**
     * Handle the voice client and song queue for a specific guild.
     *
     * @param Guild $guild
     * @return void
     */
    private function handleGuildVoiceClient(Guild $guild): void
    {
        $voiceClient = $this->getVoiceClient($guild->guild_id);
        $queue = $this->getQueueForGuild($guild->id);

        if ($voiceClient && $queue) {
            if (!$voiceClient->isSpeaking()) {
                $this->playNextSongFromQueue($voiceClient, $queue);
            }
        }
    }

    /**
     * Get the voice client for the specified guild.
     *
     * @param string $guildId
     * @return \Discord\Voice\VoiceClient|null
     */
    private function getVoiceClient(string $guildId): ?VoiceClient
    {
        return $this->discord->getVoiceClient($guildId);
    }

    /**
     * Get the song queue for the specified guild.
     *
     * @param string $guildId
     * @return UserQueue|null
     */
    private function getQueueForGuild(string $guildId): ?UserQueue
    {
        return UserQueue::where('guild_id', $guildId)->first();
    }

    /**
     * Play the next song from the queue and update the queue.
     *
     * @param VoiceClient $voiceClient
     * @param UserQueue $queue
     * @return void
     */
    private function playNextSongFromQueue(VoiceClient $voiceClient, UserQueue $queue): void
    {
        $song = json_decode($queue->queue, true);
        $voiceClient->setBitrate(320000);
        $voiceClient->playFile($song[0]);
        array_shift($song);

        if (empty($song)) {
            $queue->delete();
        } else {
            $queue->queue = json_encode($song);
            $queue->save();
        }
    }
}
