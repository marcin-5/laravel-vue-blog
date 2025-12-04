<?php

namespace App\Enums;

enum StatsSort: string
{
    case ViewsDesc = 'views_desc';
    case ViewsAsc = 'views_asc';
    case NameAsc = 'name_asc';
    case NameDesc = 'name_desc';
    case TitleAsc = 'title_asc';
    case TitleDesc = 'title_desc';
}
