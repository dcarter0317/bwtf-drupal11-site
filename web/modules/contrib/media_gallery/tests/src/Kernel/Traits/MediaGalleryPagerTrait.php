<?php

namespace Drupal\Tests\media_gallery\Kernel\Traits;

/**
 * Provides a trait for mocking the pager manager in kernel tests.
 */
trait MediaGalleryPagerTrait {

  /**
   * Create a mock pager.manager to return a provided current page value.
   *
   * @param int $current_page
   *   The current page number to return when the pager.manager is queried.
   */
  public function givenTheCurrentPageIs(int $current_page) {
    $this->container->set(
          'pager.manager', new class($current_page) {
              /** @var int */
              protected $currentPage;

              /**
               * Construct pager.manager with our provided current page
               *
               * @param int $currentPage
               *   The current page the pager should return.
               */
            public function __construct(int $currentPage) {
                $this->currentPage = $currentPage;
            }

              /**
               * Creates a new pager
               *
               * @param int $total
               *   Total number of pages.
               * @param int $limit
               *   The limit of items per page.
               */
            public function createPager(int $total, int $limit) {
                return new class($this->currentPage) {
                    /** @var int */
                    protected $currentPage;

                    /**
                     * Construct the pager with our provided current page
                     *
                     * @param int $currentPage
                     *   The current page the pager should return.
                     */
                  public function __construct(int $currentPage) {
                      $this->currentPage = $currentPage;
                  }

                    /**
                     * Get the current page
                     *
                     * @return int
                     *   The current page we provided during construction
                     */
                  public function getCurrentPage() {
                      return $this->currentPage;
                  }

                };
            }

          }
      );
  }

}
