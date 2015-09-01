Keylinks for PHP
===========================================

This PHP library allows Keylinks to be installed on any website running PHP.

## Installation ##

Keylinks can be installed as a composer package.

## Usage ##

For this library to work properly, it needs two things. Access to a fast cache mechanism (file by default). Access to the output of your website just before it is sent to the browser. This allows Keylinks to process your website in its complete form.

The Keylinks library works like a filtering function. Which means it can be used to filter a specific view file, a sentence or paragraph, or most commonly your entire website.

## Cache ##

The library uses PHPFastCache. The cache is used to store the Keylinks generated for your website so that it can access them quickly and update them frequently.

## How To Use Keylinks ##

Wrap any piece of text that you would like to create a link for in the <keylinks> tag and Keylinks will do the rest. You can even replace existing <a> links with a <keylinks> tag, and if Keylinks can improve upon it, it will, but if it can't, it will turn it back into a regular link with an <a> tag.