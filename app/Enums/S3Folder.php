<?php

namespace App\Enums;

enum S3Folder: string
{
  case CCD = 'ccd';
  case Settings = 'settings';
  case Base = 'base';
  /** Mainly used at the global level because user-avartars are global */
  case UserAvartars = 'user-avatars';
  case InstitutionGroupBanners = 'institution-group-banners';
}
