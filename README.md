# Digraph DataObject v0.5

[![Build Status](https://travis-ci.org/digraphcms/digraph-dataobject.svg?branch=v0.5)](https://travis-ci.org/digraphcms/digraph-dataobject)

*This is not going to be the final version of Digraph DataObjects.*
I will be supporting the v0.5 branch with its current interface, at least for myself, because I am using it in a few production projects. Those projects are stuck on some pretty weird old custom builds of PHP, and so this branch will be maintaining PHP 5.3 compatibility for the foreseeable future. If you're on a more recent version than PHP 5.3 (as you very much should be), you shouldn't use this branch. You should instead wait for the 1.0 release, which should hopefully be in 2018 sometime. The 1.0 branch will fix a lot of conceptual shortcomings of the alpha versions, and is designed to target modern environments and take full advantage of all the features of PHP 7.1 that make it finally act something like a grown-up programming language.

So I guess in a nutshell: This version is relatively stable, and the author is actually using it in production. The interface shouldn't change significantly, unless I find something absolutely terrible. You still probably shouldn't use it, though, because it's targeting a truly decrepit version of PHP and better things are on the way.

Also this version will probably never be properly documented.

## MIT License

Copyright (c) 2017 Joby Elliott <joby@byjoby.com>

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
