<?php
namespace App\Http\Controllers\Home;

use App\Models\Announcement;
use App\Http\Controllers\Controller;

class AnnouncementController extends Controller
{    
    function apiIndex($userLastAnnouncementId = 0)
    {
        $announcements = Announcement::getMessages($userLastAnnouncementId);
        
        die(json_encode([
            SUCCESSFUL => true, 
            MESSAGE => '', 
            'announcements' => $announcements
        ]));
    }
    
    
}





