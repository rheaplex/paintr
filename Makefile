# Makefile -  The Makefile for paint's Lisp scripts.
# Copyright (C) 2009  Rob Myers rob@robmyers.org
#
# This file is part of paintr.
#
# paintr is free software; you can redistribute it and/or 
# modify it under the terms of the GNU General Public License as published 
# by the Free Software Foundation; either version 3 of the License, or
# (at your option) any later version.
#
# paintr is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.

all: paintr paintr-rss

paintr:	paintr.lisp
	sbcl --noinform --load "paintr.lisp" \
	--eval "(sb-ext:save-lisp-and-die \"paintr\" :executable t \
					  :toplevel #'run)"

paintr-rss: paintr-rss.lisp
	sbcl --noinform --load "paintr-rss.lisp" \
	--eval "(sb-ext:save-lisp-and-die \"paintr-rss\" :executable t \
			  		  :toplevel #'run)"

clean:
	rm -f paintr
	rm -f paintr-rss
	rm -f *.fasl

distclean: clean
