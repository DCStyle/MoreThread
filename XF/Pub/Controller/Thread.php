<?php

namespace DC\MoreThread\XF\Pub\Controller;

use DC\MoreThread\Repository\MoreThread;
use XF\Mvc\ParameterBag;
use XF\Mvc\Reply\View;

class Thread extends XFCP_Thread
{
    public function actionIndex(ParameterBag $params)
    {
        $parent = parent::actionIndex($params);

        if ($parent instanceof View)
        {
	        $options = \XF::options();

	        $thread = $this->assertViewableThread($params->thread_id, $this->getThreadViewExtraWith());

	        /** @var MoreThread $moreThreadRepo */
	        $moreThreadRepo = $this->repository('DC\MoreThread:MoreThread');

	        if ($options->dcMoreThread_relatedThreads &&
		        !in_array($thread->node_id, $options->dcMoreThread_excludedForums))
	        {
		        $relatedThreads = $moreThreadRepo->findRelatedThreads($thread);

		        $parent->setParam('relatedThreads', $relatedThreads);
	        }

	        if ($options->dcMoreThread_mostView &&
		        !in_array($thread->node_id, $options->dcMoreThread_excludedForums))
	        {
		        $mostViewThreads = $moreThreadRepo->findMostViewedThreads($thread);

		        $parent->setParam('mostView', $mostViewThreads);
	        }

	        if ($options->dcMoreThread_latestThreads &&
		        !in_array($thread->node_id, $options->dcMoreThread_excludedForums))
	        {
		        $latestThreads = $moreThreadRepo->findLatestThreads($thread);

		        $parent->setParam('latestThreads', $latestThreads);
	        }
        }

        return $parent;
    }
}