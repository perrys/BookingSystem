<?php

# This should probably be a class, but you can only have protected
# class members in PHP 5, so we won't bother

function get_mrbs_version()
{
  # MRBS developers, make sure to update this string before each release
  $mrbs_version = "2016.12.13-1";

  return $mrbs_version;
}

?>
