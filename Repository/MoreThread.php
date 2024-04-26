<?php

namespace DC\MoreThread\Repository;

use XF\Entity\Thread;
use XF\Mvc\Entity\Repository;

class MoreThread extends Repository
{
	public function findRelatedThreads(Thread $thread)
	{
		$options = $this->options();
		$limit = $options->dcMoreThread_relatedThreads_limit;
		
		$threadTitle = explode(' ', $thread->title);
		
		$threadIds = [];
		
		$maxResults = $options->dcMoreThread_relatedThreads_limit;
		
		foreach($threadTitle AS $word)
		{
			$finder = $this->getThreadFinder();
			$threads = $finder
				->where('thread_id', '<>', $thread->thread_id)
				->where('title', 'LIKE', $finder->escapeLike($word, '%?%'));
			
			if ($options->dcMoreThread_mostView_days)
			{
				$now = \XF::$time;
				$days = $now - $options->dcMoreThread_relatedThreads_days * 86400;
				
				$threads->where('post_date', '>=', $days);
			}
			
			if ($options->dcMoreThread_relatedThreads_sameForum)
			{
				$threads->where('node_id', $thread->node_id);
			}
			else
			{
				$threads->where('node_id', $options->dcMoreThread_relatedThreads_forums);
			}
			
			$threads
				->order('post_date', 'DESC')
				->limit($maxResults)
				->fetch();
			
			// convert object to array
			foreach ($threads as $k => $v)
			{
				$threadIds[] = ['thread_id', '=', $v->thread_id];
			}
			
			$maxResults = $maxResults - $threads->total();
			
			if ($maxResults <= 0) break;
		}
		
		if ($threadIds)
		{
			$relatedThreadsFinder = $this->getThreadFinder()
				->whereOr($threadIds)
				->order('post_date', 'DESC')
				->limit(max($limit * 4, 20));
			
			foreach($relatedThreads = $relatedThreadsFinder->fetch() AS $threadId => $thread)
			{
				if (!$thread->canView()
					|| $thread->isIgnored()
				)
				{
					unset($relatedThreads[$threadId]);
				}
			}
			
			$relatedThreads = $relatedThreads->slice(0, $limit, true);
		}
		else
		{
			$relatedThreads = $this->em->getEmptyCollection();
		}
		
		return $relatedThreads;
	}
	
	public function findMostViewedThreads(Thread $thread)
	{
		$options = $this->options();
		$limit = $options->dcMoreThread_mostView_limit;
		
		$mostViewThreadsFinder = $this->getThreadFinder();
		
		if ($options->dcMoreThread_mostView_sameForum)
		{
			$mostViewThreadsFinder->where('node_id', $thread->node_id);
		}
		else
		{
			$mostViewThreadsFinder->where('node_id', $options->dcMoreThread_mostView_forums);
		}
		
		if ($options->dcMoreThread_mostView_days)
		{
			$date = new \DateTime();
			$now = $date->getTimestamp();
			$days = $now - $options->dcMoreThread_mostView_days * 86400;
			$mostViewThreadsFinder->where('post_date', '>=', $days);
		}
		
		$mostViewThreadsFinder
			->where('thread_id', '<>', $thread->thread_id)
			->order('view_count', 'DESC')
			->limit(max($limit * 4, 20));
		
		foreach($mostViewThreads = $mostViewThreadsFinder->fetch() AS $threadId => $thread)
		{
			if (!$thread->canView()
				|| $thread->isIgnored()
			)
			{
				unset($mostViewThreads[$threadId]);
			}
		}
		
		return $mostViewThreads->slice(0, $limit, true);
	}
	
	public function findLatestThreads(Thread $thread)
	{
		$options = $this->options();
		$limit = $options->dcMoreThread_latestThreads_limit;
		
		$latestThreadsFinder = $this->getThreadFinder();
		
		if ($options->dcMoreThread_latestThreads_sameForum)
		{
			$latestThreadsFinder->where('node_id', $thread->node_id);
		}
		else
		{
			$latestThreadsFinder->where('node_id', $options->dcMoreThread_latestThreads_forums);
		}
		
		if ($options->dcMoreThread_latestThreads_days)
		{
			$now = \XF::$time;
			$days = $now - $options->dcMoreThread_latestThreads_days * 86400;
			$latestThreadsFinder->where('post_date', '>=', $days);
		}
		
		$latestThreadsFinder
			->where('thread_id', '<>', $thread->thread_id)
			->order('post_date', 'DESC')
			->limit(max($limit * 4, 20));
		
		foreach($latestThreads = $latestThreadsFinder->fetch() AS $threadId => $thread)
		{
			if (!$thread->canView()
				|| $thread->isIgnored()
			)
			{
				unset($latestThreads[$threadId]);
			}
		}
		
		return $latestThreads->slice(0, $limit, true);
	}

	/**
	 * @return \XF\Mvc\Entity\Finder|\XF\Finder\Thread
	 */
	protected function getThreadFinder()
	{
		return $this->finder('XF:Thread')
			->where('discussion_state', '=', 'visible')
			->where('discussion_type', '<>', 'redirect')
			->where('post_date', '<=', \XF::$time);
	}
}