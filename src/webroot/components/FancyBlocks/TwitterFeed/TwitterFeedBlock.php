<?php

namespace Project\FancyBlocks\TwitterFeed;

use Supra\Controller\Pages\BlockController;
use Supra\ObjectRepository\ObjectRepository;
use Supra\Controller\Pages\Request\PageRequestEdit;
use Supra\Configuration\Loader\WriteableIniConfigurationLoader;

class TwitterFeedBlock extends BlockController
{
    
    /*
     * @var string
     */    
    protected $cacheId = 'twitter_feed_cache';
    
    /*
     * Cache lifetime, 15 minutes by default
     * @var integer
     */
    protected $cacheLifeTime = 900;
    
    /*
     * @var string
     */
    protected $accessToken = null;
    
    /*
     * @var string
     */
    protected $accessTokenSecret = null;
        
    /*
     * @var \SupraSite\Twitter\TwitterDataProvider
     */
    protected $twitterProvider;
    
    /*
     * @var string
     */
    protected $feedUrl = 'https://api.twitter.com/1.1/statuses/user_timeline.json';
    
    /*
     * @var array
     */
    protected $feedParameters = array(
        'trim_user' => true,
        'include_rts' => false,
        'exclude_replies' => true,
    );
    
        
	public function doExecute()
    {
        $tweets = array();
        $hasErrors = true;
        $response = $this->getResponse();
        $request = $this->getRequest();
        
        $result = $this->loadConfiguration();
        
        if ($result['success']) {
            
            $tweets = $this->getTwitterfeed($tweets);
            
            if(is_object($tweets) && $tweets->errors) {
               $errorMessages[] = 'Unable to get Twitter feed. Please try generating another PIN code in Twitter Settings.';
            } else {
                $hasErrors = false;
            }
        } else {
            $errorMessages = $result['errors'];
        }
        
        
        if ($hasErrors) {
            if ($request instanceof PageRequestEdit) {
                $response
                        ->assign('errors', $errorMessages)
                        ->outputTemplate('configuration-missing.html.twig');
            }            
        } else {
                $response
                        ->assign('tweets', $tweets)
                        ->outputTemplate('index.html.twig');         
        }
    }
    
    
    protected function loadConfiguration()
    {
        $data = array(
            'success' => true,
        );
        
        $writeableIni = ObjectRepository::getIniConfigurationLoader('#twitter');
		if (!($writeableIni instanceof WriteableIniConfigurationLoader)) {
			throw new \RuntimeException('Twitter configuration loader is missing');
		}
		
		if (
			$writeableIni->hasKey('twitter', 'access_token') && 
			$writeableIni->hasKey('twitter', 'access_token_secret')
		) {
			$this->accessToken = $writeableIni->getValue('twitter', 'access_token');
			$this->accessTokenSecret = $writeableIni->getValue('twitter', 'access_token_secret');
		}
		
        if (!$this->accessToken || !$this->accessTokenSecret) {
            $data['success'] = false;
            $data['errors'][] = 'Twitter is not properly configured. Please set it up in Site Settings';
        }
        
        $this->twitterProvider = ObjectRepository::getObject($this, 'Supra\Social\Twitter\TwitterDataProvider');
        $this->twitterProvider->setTokens($this->accessToken, $this->accessTokenSecret);
        
        $twitterAccount = $this->getPropertyValue('account');
        if (!$twitterAccount) {
            $data['success'] = false;
            $data['errors'][] = 'Please set Twitter account in Twitter Feed Block Properties.';
        } else {
            $this->feedParameters['screen_name'] = $twitterAccount;
        }
        
        return $data;
    }
    
    
    protected function getTwitterFeed()
    {
        $tweets = array();
        $cacheAdapter = ObjectRepository::getCacheAdapter($this);

        if ($cacheAdapter->contains($this->cacheId)) {
            $tweets = $cacheAdapter->fetch($this->cacheId);
            $tweets = $this->formatTweets($tweets);
        } else {
            $tweets = $this->twitterProvider->get($this->feedUrl, $this->feedParameters);
            if (is_array($tweets)) {
                
                $limit = $this->getPropertyValue('limit');
                if ($limit) {
                    $tweets = array_slice($tweets, 0, $limit);
                }
                                
                $cacheAdapter->delete($this->cacheId);
                $cacheAdapter->save($this->cacheId, $tweets, $this->cacheLifeTime);
                $tweets = $this->formatTweets($tweets);
            }
        }

        return $tweets;
    }
    
    
    protected function formatTweets($tweets)
    {
        foreach($tweets as &$tweet) {
            $tweet->created_at = $this->formatTweetDate($tweet->created_at);
            $tweet->text = $this->formatTweetText($tweet->text);
        }
        
        return $tweets;
    }
    
    
    private function formatTweetDate($date)
    {
        $tweetTimeStamp = strtotime($date);
        $now = time();
        
        $seconds = $now - $tweetTimeStamp;

        if ($seconds <= 1) {
            return 'just now';
        } else if ($seconds < 20) {
            return $seconds . ' seconds ago';
        } else if ($seconds < 40) {
            return 'half a minute ago';
        } else if ($seconds < 60) {
            return 'less than a minute ago';
        } else if ($seconds <= 90) {
            return 'one minute ago';
        } else if ($seconds <= 3540) {
            return round($seconds / 60) . ' minutes ago';
        } else if ($seconds <= 5400) {
            return '1 hour ago';
        } else if ($seconds <= 86400) {
            return round($seconds / 3600) . ' hours ago';
        } else if ($seconds <= 129600) {
            return '1 day ago';
        } else if ($seconds < 604800) {
            return round($seconds / 86400) . ' days ago';
        } else if ($seconds <= 777600) {
            return '1 week ago';
        }
        
        //Format %d %m %y
        $now = new \DateTime('now');
        $tweetDate = new \DateTime();
        $tweetDate->setTimestamp($tweetTimeStamp);
        
        $diff = $now->diff($tweetDate);
        if ($diff->y > 1) {
            return $tweetDate->format('d F Y');
        } else {
            return $tweetDate->format('d F');
        }
    }
    
    
    private function formatTweetText($text)
    {
        $temp = $text;
        
        //http link
        $temp = preg_replace_callback('/(\b(https?|ftp|file):\/\/[\-A-Z0-9+&@#\/%?=~_|!:,.;]*[\-A-Z0-9+&@#\/%=~_|])/i', function($matches) { 
            $result = '<a href="' . $matches[0] . '" target="_blank">' . $matches[0] . '</a>';
            return $result;
        }, $temp);
        
        //@username
        $temp = preg_replace_callback('/(^|\s)@(\w+)/', function($matches) {
            $username = trim(str_replace("@", "", $matches[0]));
            $result = '<a href="http://twitter.com/' . $username . '" target="_blank">' . $matches[0] . '</a>';
            return $result;
        }, $temp);
        
        // #hashtag
        $temp = preg_replace_callback('/(^|\s)#(\w+)/', function($matches) {
            $hashtag = trim(str_replace("#","%23", $matches[0]));
            return '<a href="http://search.twitter.com/search/' . $hashtag . '" target="_blank">' . $matches[0] . '</a>';
        }, $temp);
    
        return $temp;
    }
}