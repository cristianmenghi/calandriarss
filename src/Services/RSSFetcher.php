<?php

namespace App\Services;

use App\Models\Source;
use App\Models\Article;
use SimplePie\SimplePie;

class RSSFetcher
{
    public function fetchAll()
    {
        $sources = Source::getDueForUpdate();
        $results = [];

        foreach ($sources as $source) {
            $results[$source['name']] = $this->fetchSource($source);
        }
        
        return $results;
    }

    public function fetchSource($source)
    {
        $feed = new SimplePie();
        $feed->set_feed_url($source['rss_feed_url']);
        $feed->enable_cache(false); // We handle DB caching
        $feed->init();
        $feed->handle_content_type();

        if ($feed->error()) {
            return ['status' => 'error', 'message' => $feed->error()];
        }

        $count = 0;
        foreach ($feed->get_items() as $item) {
            $title = $item->get_title();
            $url = $item->get_permalink();
            $hash = hash('sha256', $url . $title);

            if (Article::exists($hash)) {
                continue;
            }

            $date = $item->get_date('Y-m-d H:i:s');
            if (!$date) {
                $date = date('Y-m-d H:i:s');
            }

            // Extract description (summary/excerpt) and content (full text)
            $description = $item->get_description();
            $content = $item->get_content();

            // SimplePie often puts the full content in description if content is missing,
            // or vice-versa. We want to ensure description is a clean summary.
            if (empty($description) && !empty($content)) {
                $description = $content;
            }
            
            $cleanDescription = strip_tags($description);
            // Limit description length if it's too long (common if it contains the whole post)
            if (mb_strlen($cleanDescription) > 500) {
                $cleanDescription = mb_substr($cleanDescription, 0, 497) . '...';
            }

            // Extract image (basic implementation)
            $image = null;
            if ($enclosure = $item->get_enclosure()) {
                $image = $enclosure->get_link();
            }

            Article::create([
                'source_id' => $source['id'],
                'title' => $title,
                'url' => $url,
                'description' => $cleanDescription,
                'content' => $content,
                'author' => $item->get_author() ? $item->get_author()->get_name() : null,
                'published_at' => $date,
                'image_url' => $image,
                'guid' => $item->get_id(),
                'hash' => $hash
            ]);
            $count++;
        }
        
        Source::updateLastFetched($source['id']);

        return ['status' => 'success', 'fetched' => $count];
    }
}
