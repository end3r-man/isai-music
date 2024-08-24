<?php

namespace App\Services;

use App\Models\Guild;
use App\Models\UserQueue;
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
    public function handle(): void
    {
        $guilds = Guild::all();

        foreach ($guilds as $key => $value) {
            $vc = $this->discord()->getVoiceClient($value->guild_id);
            $list = UserQueue::where('guild_id', $value->id)->first();
            
            if ($vc && !empty($list->queue)) {
                $song = json_decode($list->queue, true);

                if ($vc && !$vc->isSpeaking()) {

                    $vc->setBitrate(320000);
                    $vc->playFile($song[0]);

                    array_shift($song);

                    if (empty($song)) {
                        $list->delete();
                    } else {
                        $list->queue = json_encode($song);
                        $list->save();
                    }
                }
            }
        }
    }
}
