<?php
namespace Concrete\Package\MatomoCampaignLinks\MenuItem\MatomoCampaignLinks;

use Concrete\Core\Application\UserInterface\Menu\Item\Controller as MenuItemController;

class Controller extends MenuItemController
{
    public function displayItem()
    {
        return true;
    }
}
