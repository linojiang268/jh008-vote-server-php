<?php
namespace Jihe\Repositories;

use Jihe\Contracts\Repositories\MessageRepository as MessageRepositoryContract;
use Jihe\Entities\Message as MessageEntity;
use Jihe\Models\Message;
use Illuminate\Database\Eloquent\Model;

class MessageRepository implements MessageRepositoryContract
{
    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\MessageRepository::add()
     */
    public function add(MessageEntity $message)
    {
        return Message::create($this->morphToAttributes($message))->toEntity();
    }
    
    private function morphToAttributes(MessageEntity $message) 
    {
        return [
            'team_id'       => is_null($message->getTeam()) ? null : $message->getTeam()->getId(),
            'activity_id'   => is_null($message->getActivity()) ? null : $message->getActivity()->getId(),
            'user_id'       => is_null($message->getUser()) ? null : $message->getUser()->getId(),
            'content'       => $message->getContent(),
            'type'          => $message->getType(),
            'attributes'    => $message->getAttributes() ? json_encode($message->getAttributes()) : null,
            'notified_type' => $message->getNotifiedType(),
        ];
    }
    
    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\MessageRepository::findSystemMessagesOf()
     */
    public function findSystemMessagesOf($user, $page, $size, $lastCreatedAt = null)
    {
        $query = Message::whereNull('team_id')
                        ->whereNull('activity_id')
                        ->where(function ($query) use ($user) {
                                    return $query->whereNull('user_id')
                                                 ->orWhere('user_id', $user);
                               });
                        
        $query->where('created_at', '>', $lastCreatedAt)
            ->orderBy('created_at', 'desc')
              ->orderBy('id', 'desc');
        
        $total = $query->getCountForPagination()->count();

        return [
                $total,
                array_map(function (Message $message) {
                             return $message->toEntity();
                         }, $query->get()->all())];
                      
    }
    
    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\MessageRepository::findUserMessages()
     */
    public function findUserMessages($teams, $activities, $user, $page, $size, $lastCreatedAt = null)
    {
        $query = Message::with(['team', 'activity'])
                        ->where('created_at', '>', $lastCreatedAt)
                        ->orderBy('created_at', 'desc')
                        ->orderBy('id', 'desc');

        $query->where(function ($query) use ($user, $teams, $activities) {
            $query->orWhere(function ($query) use ($user) {
                $query->whereNotNull('team_id')
                      ->where('user_id', $user);
            });

            if (!empty($teams)) {
                $query->orWhere(function($query) use ($teams) {
                    $query->where(function ($query) use ($teams) {
                        if (is_array($teams)) {
                            $query->whereIn('team_id', $teams);
                        } else {
                            $query->where('team_id', $teams);
                        }

                        $query->whereNull('activity_id')
                            ->whereNull('user_id');
                    });
                });
            }

            if (!empty($activities)) {
                $query->orWhere(function ($query) use ($activities) {
                    if (is_array($activities)) {
                        $query->whereIn('activity_id', $activities);
                    } else {
                        $query->where('activity_id', $activities);
                    }

                    $query->whereNull('user_id');
                });
            }
        });
        
        $total = $query->getCountForPagination()->count();
                                  
        return [
                $total,
                array_map(function (Message $message) {
                             return $message->toEntity();
                         }, $query->get()->all())];
    }
    
    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\MessageRepository::findUserMessagesOf()
     */
    public function findUserMessagesOf($team, $activities, $user, $page, $size, $lastCreatedAt = null)
    {
        $query = Message::with(['team', 'activity'])->where('team_id', $team)
                        ->where(function ($query) use ($user) {
                            $query->whereNull('user_id')
                                  ->orWhere('user_id', $user);
                        })
                        ->where(function ($query) use ($activities) {
                            $query->whereNull('activity_id');
                            if (!empty($activities)) {
                                if (is_array($activities)) {
                                    $query->orWhereIn('activity_id', $activities);
                                } else {
                                    $query->orWhere('activity_id', $activities);
                                }
                            }
                        });
        
        $query->where('created_at', '>', $lastCreatedAt)
              ->orderBy('created_at', 'desc')
              ->orderBy('id', 'desc');
        
        $total = $query->getCountForPagination()->count();
                                  
        return [
                $total,
                array_map(function (Message $message) {
                             return $message->toEntity();
                         }, $query->get()->all())];
    }
    
    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\MessageRepository::findSystemMessages()
     */
    public function findSystemMessages($page, $size) 
    {
        $query = Message::whereNull('team_id')
                        ->whereNull('activity_id');
        
        $query->orderBy('created_at', 'desc')
              ->orderBy('id', 'desc');
        
        $total = $query->getCountForPagination()->count();
        
        return [
            $total,
            array_map(function (Message $message) {
                    return $message->toEntity();
            }, $query->get()->all())
        ];
    }
    
    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\MessageRepository::findTeamMessages()
     */
    public function findTeamMessages($team, $page, $size)
    {
        $query = Message::where('team_id', $team)
                        ->whereNull('activity_id');
    
        $query->orderBy('created_at', 'desc')
              ->orderBy('id', 'desc');
    
        $total = $query->getCountForPagination()->count();
    
        return [
            $total,
            array_map(function (Message $message) {
                return $message->toEntity();
            }, $query->get()->all())
        ];
    }
    
    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\MessageRepository::findActivityMessages()
     */
    public function findActivityMessages($activity, $page, $size)
    {
        $query = Message::where('activity_id', $activity);
    
        $query->orderBy('created_at', 'desc')
              ->orderBy('id', 'desc');
    
        $total = $query->getCountForPagination()->count();
    
        return [
            $total,
            array_map(function (Message $message) {
                return $message->toEntity();
            }, $query->get()->all())
        ];
    }

    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\MessageRepository::findMessagesOfUser()
     */
    public function findMessagesOfUser($user, array $teams = [], array $activities = [], $lastCreatedAt, $onlyCount = true)
    {
        $query = Message::with(['team', 'activity'])
                        ->where('created_at', '>', $lastCreatedAt)
                        ->orderBy('created_at', 'desc')
                        ->orderBy('id', 'desc');

        $query->where(function ($query) use ($user, $teams, $activities) {
            // special user (system or team or activity messages)
            $query->orWhere(function ($query) use ($user) {
                $query->where('user_id', $user);
            });

            // system message to all
            $query->orWhere(function ($query) {
                $query->whereNull('team_id')
                      ->whereNull('activity_id')
                      ->whereNull('user_id');
            });

            // team messages to all team members
            if (!empty($teams)) {
                $query->orWhere(function ($query) use ($teams) {
                    $query->whereIn('team_id', $teams)
                          ->whereNull('activity_id')
                          ->whereNull('user_id');
                });
            }

            // team messages to all activity members
            if (!empty($activities)) {
                $query->orWhere(function ($query) use ($activities) {
                    $query->whereIn('activity_id', $activities)
                          ->whereNull('user_id');
                });
            }
        });

        $total = $query->count();
        if ($onlyCount) {
            return $total;
        }

        return [
            $total,
            array_map(function (Message $message) {
                return $message->toEntity();
            }, $query->get()->all())];
    }

    /**
     * (non-PHPdoc)
     * @see \Jihe\Contracts\Repositories\MessageRepository::countActivityNotices()
     */
    public function countActivityNotices($activity, $options = [])
    {
        $query = Message::where('activity_id', $activity);

        if (array_get($options, 'only_mass', false)) {
            $query->whereNull('user_id');
        }

        return $query->count();
    }
}
