<?php
namespace App\DTO;

class BreadCrumb
{
    function __construct(
        public string $title,
        public string $route = '',
        public string $icon = '',
        public bool $active = false,
    ){
    }

    function active() {
        $this->active = true;
        return $this;
    }

    function icon(string $icon) {
        $this->icon = $icon;
        return $this;
    }

    function route(string $route) {
        $this->route = route($route);
        return $this;
    }

    function activeClass() {
        return $this->active ? 'active' : '';
    }
}
