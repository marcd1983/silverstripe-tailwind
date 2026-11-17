<?php

namespace {
    use SilverStripe\ORM\DataList;
    use Services\ServicePage;
    use App\Models\Partner;
    use App\Models\Testimony;

    class HomePageController extends PageController
    {
        private static $allowed_actions = [];

        public function init()
        {
            parent::init();
        }

        public function getPartners(): DataList
        {
            return Partner::get()
                ->sort([
                    'SortOrder' => 'ASC',
                    'Title'     => 'ASC',
                ]);
        }

        public function getTestimonies(): DataList
        {
            return Testimony::get()
                ->sort([
                    'SortOrder' => 'ASC',
                    'Title'     => 'ASC',
                ]);
        }

        public function getHomeServicesAsc(): DataList
        {
            return ServicePage::get()
                ->filter('ShowOnHome', true)
                ->sort([
                    'SortOrder' => 'ASC',
                    'Title'     => 'ASC',
                ]);
        }

        /**
         * Returns ShowOnHome services newest/highest sort order first.
         */
        public function getHomeServicesDesc(): DataList
        {
            return ServicePage::get()
                ->filter('ShowOnHome', true)
                ->sort([
                    'SortOrder' => 'DESC',
                    'Title'     => 'DESC',
                ]);
        }

        /**
         * Returns featured services only — useful for a spotlight section.
         */
        public function getFeaturedServices(): DataList
        {
            return ServicePage::get()
                ->filter('Featured', true)
                ->sort([
                    'SortOrder' => 'ASC',
                    'Title'     => 'ASC',
                ]);
        }
    }
}
