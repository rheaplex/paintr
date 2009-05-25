;; paintr.lisp -  Art in the age of network services.
;; Copyright (C) 2009  Rob Myers rob@robmyers.org
;;
;; This file is part of paintr.
;;
;; paintr is free software; you can redistribute it and/or 
;; modify it under the terms of the GNU General Public License as published 
;; by the Free Software Foundation; either version 3 of the License, or
;; (at your option) any later version.
;;
;; paintr is distributed in the hope that it will be useful,
;; but WITHOUT ANY WARRANTY; without even the implied warranty of
;; MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
;; GNU General Public License for more details.
;;
;; You should have received a copy of the GNU General Public License
;; along with this program.  If not, see <http://www.gnu.org/licenses/>.

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
;; Requires
;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;

(require 'cl-ppcre)
(require 'drakma)

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
;; Configuration
;; Set these by loading a Lisp file before this one that defvars them.
;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;

(defvar +paintr-directory-path+ "./")
(defvar +flickr-api-key+ "")

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
;; Utilities
;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;

(defun die (reason)
  "Alert and quit"
  (print reason)
  (quit))

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
;; Call local command line tools
;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;

(defun autotrace (input-file-path output-file-path colour-count)
  "Convert the input file to svg with colour-count colours"
  (sb-ext:run-program "autotrace" (list "--output-format" "svg"
					"--output-file" output-file-path
					"--color-count" (format nil 
								"~a" 
								colour-count)
					"--despeckle-level"  "10"
					input-file-path)
		      :search t :wait t))

(defun wget (remote-url local-file-path)
  "Get the remote url to a local file"
  (sb-ext:run-program "wget" (list "-O" local-file-path remote-url)
		      :search t :wait t))

(defun gzip (file-path)
  "Gzip the file and give it a specific suffix, svgz for example"
  (sb-ext:run-program "gzip" (list file-path )
		      :search t :wait t))

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
;; Current ID
;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;

(defparameter +current-id+ 0)

(defparameter +current-id-file+ "current-id")

(defun load-current-id ()
  "Load or create the current id"
  (let ((id-file-path (format nil "~a/~a" +paintr-directory-path+
			      +current-id-file+)))
    (when (probe-file id-file-path)
      (with-open-file (id-file id-file-path)
	(setf +current-id+ (read id-file)))))
  (incf +current-id+))

(defun save-current-id ()
  "Save the current id"
  (with-open-file (id-file (format nil "~a/~a" +paintr-directory-path+
				   +current-id-file+)
			   :direction :output
			   :if-exists :supersede)
    (format id-file "~a" +current-id+)))

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
;; Colr
;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;

(defun colr-random-palette ()
  "Get the first palette from the colr rss"
  (multiple-value-bind (body code) 
      (drakma:http-request "http://colr.org/rss/scheme/random")
    (if (= code 200)
      body
      nil)))

(defun palette-name (palette)
  "Get the name from the palette XML"
  (ppcre:register-groups-bind (title) ("<title>(.+?)( \\(colr.org\\))?<\\/title>" palette)
    title))

(defun palette-tags (palette)
  "Get the tags from the palette XML"
  (ppcre:register-groups-bind (tags) ("<tags>(.+?)<\\/tags>" palette)
			      (if tags
				  (ppcre:split "\\s+" tags)
				  nil)))

(defun palette-colours (palette)
  "Get the colours as strings from the palette XML"
    (ppcre:register-groups-bind (colors) ("<colors>(.+?)<\\/colors>" palette)
				(if colors
				    (ppcre:split "\\s+" colors)
				    nil)))

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
;; Flickr
;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;

(defun flickr-photo-tag-search (tags)
  "Fetch the xml for a single BY-SA photo matching the tags"
  (multiple-value-bind (body code) 
      (drakma:http-request "http://api.flickr.com/services/rest/"
			   :method :get
			   :parameters 
			   (list '("method" ."flickr.photos.search")
				 (cons "api_key" +flickr-api-key+)
				 (cons "tags" (format nil "~{~a~^,~}" tags))
				 '("license" . "5") ;; BY-SA
				 '("per_page" . "1")))
    
    (if (= code 200)
      body
      nil)))

  
(defun farm-id (flickr-photo-xml)
  "Get the farm id from a single photo's xml"
  (ppcre:register-groups-bind (id) ("farm=\\\"([^\\\"]+)\\\"" 
				    flickr-photo-xml)
     id))

(defun server-id (flickr-photo-xml)
  "Get the server id from a single photo's xml"
  (ppcre:register-groups-bind (id) ("server=\\\"([^\\\"]+)\\\"" 
				    flickr-photo-xml)
    id))

(defun photo-id (flickr-photo-xml)
  "Get the photo id from a single photo's xml"
  (ppcre:register-groups-bind (id) ("id=\\\"([^\\\"]+)\\\"" 
				    flickr-photo-xml)
    id))

(defun secret-id (flickr-photo-xml)
  "Get the secret id from a single photo's xml"
  (ppcre:register-groups-bind (id) ("secret=\\\"([^\\\"]+)\\\"" 
				    flickr-photo-xml)
    id))

(defun photo-jpeg-url (flickr-photo-list-xml)
  "Construct the default jpeg url for a single photo from its xml"
  (format nil "http://farm~a.static.flickr.com/~a/~a_~a.jpg"
	  (farm-id flickr-photo-list-xml)
	  (server-id flickr-photo-list-xml)
	  (photo-id flickr-photo-list-xml)
	  (secret-id flickr-photo-list-xml)))

(defun photo-title (flickr-photo-xml)
  "Get the photo's title from a single photo's xml"
  (ppcre:register-groups-bind (id) ("title=\\\"([^\\\"]+)\\\"" 
				    flickr-photo-xml)
    id))

(defun photo-owner-id (flickr-photo-xml)
  "Get the owner id from a single photo's xml"
  (ppcre:register-groups-bind (id) ("owner=\\\"([^\\\"]+)\\\"" 
				    flickr-photo-xml)
    id))

(defun flickr-person-details (user-id)
  "Get the xml from flickr for the given person id"
  (multiple-value-bind (body code) 
      (drakma:http-request "http://api.flickr.com/services/rest/"
			   :parameters 
			   (list '("method" . "flickr.people.getInfo")
				 (cons "api_key" +flickr-api-key+)
				 (cons "user_id" user-id)))
    (if (= code 200)
      body
      nil)))

(defun person-username (person-xml)
  "Get the person's name from a single person's xml"
  (ppcre:register-groups-bind (name) ("<username>([^<]*)</username>" 
				    person-xml)
    name))

(defun person-profile-url (person-xml)
  "Get the person's profile url from a single person's xml"
  (ppcre:register-groups-bind (url) ("<profileurl>([^<]*)</profileurl>" 
				    person-xml)
    url))

(defun person-photos-url (person-xml)
  "Get the person's phot url from a single person's xml"
  (ppcre:register-groups-bind (url) ("<photosurl>([^<]*)</photosurl>" 
				    person-xml)
    url))

(defun photo-html-url (the-photo-id person-xml)
  "Construct the html url for a single photo from its xml and its user details"
  (format nil "~a~a/" (person-photos-url person-xml) the-photo-id))

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
;; SVG
;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;

(defun choose-randomly (choices)
  "Choose one of the parameters randomly."
  (nth (random (list-length choices)) 
       choices))

(defun random-style (colour-list)
  "Generate a random colour style string"
  (format nil "style=\"fill: #~a; stroke: none;\"" 
	  (choose-randomly colour-list)))

(defun recolour-svg (svg-text colour-list)
  "Replace all the styles in the svg with a random colour style"
  (ppcre:regex-replace-all "style\\s*=\\s*\"[^\"]*\"" 
			   svg-text 
			   #'(lambda (match &rest registers)
			       (declare (ignore match registers))
			       (random-style colour-list))))

(defun svg-dimensions (svg-text)
  "Get the width and height of the svg file"
  (ppcre:register-groups-bind (width height) 
			      ("<svg width=\"([^\"]+)\" height=\"([^\"])+\">"
			       svg-text)
			      (values width height)))

(defun fix-svg (svg-text)
  "Fix autotrace's svg header for Firefox"
  (ppcre:regex-replace "\<svg"
		       svg-text
		       "<!DOCTYPE svg PUBLIC \"-//W3C//DTD SVG 1.1//EN\" \"http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd\"> <svg xmlns=\"http://www.w3.org/2000/svg\" version=\"1.1\""))

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
;; HTML Fragment generation
;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;

(defun tag-or-tags (tag-list)
  "Single or plural?"
  (if (cdr tag-list)
      "tags"
      "tag"))

(defun format-tags (tag-list)
  "Format the tags into a comma-separated list"
  (format nil "<i>~{~a~^, ~}</i>" tag-list))

#|
which had the " . tag_or_tags ($flickr_photo_tags) .  " " . 
    format_tags ($flickr_photo_tags) .  
    "
|#

(defun save-writeup (filename palette-name palette-tags photourl photoname 
		     photousername)
  "Save an html fragment describing how the work was made and satisfying BY-SA"
  (with-open-file (file filename :direction :output :if-exists :supersede)
    (format file "<strong>How I made this image.</strong><br />~%I found a palette at colr called '~a' with the following ~a: ~a.<br />~%I searched for those tags on flickr and found an image called <a href=\"~a\">~a</a> by ~a.<br />~%I traced that using autotrace and applied the colr palette to it.<br />~%This image is licenced under the <a href='http://creativecommons.org/licenses/by-sa/3.0/'>~%Creative Commons Attribution Share-Alike Licence</a>"
	    palette-name
	    (tag-or-tags palette-tags)
	    (format-tags palette-tags)
	    photourl 
	    photoname 
	    photousername)))

;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
;; Main flow of control
;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;

(defun jpeg-file-path ()
  "The absolute file path for the local copy of the current flickr jpeg file"
  (format nil "/tmp/paintr-~a.jpg" +current-id+))

(defun svg-file-path ()
  "The absolute file path for the current svg file"
  (format nil "~a/~a.svg" +paintr-directory-path+ +current-id+))

(defun svg-gz-file-path ()
  "The absolute file path for the current svg.gz file"
  (format nil "~a/~a.svg.gz" +paintr-directory-path+ +current-id+))

(defun svgz-file-path ()
  "The absolute file path for the current svgz file"
  (format nil "~a/~a.svgz" +paintr-directory-path+ +current-id+))

(defun description-file-path () 
  "The absolute file path for the html description of the current svg file"
  (format nil "~a/~a.html" +paintr-directory-path+ +current-id+))

;;FIXME Divide into functions and allow to fail gracefully

(defun cleanup ()
  "Delete temporary files"
  (when (probe-file (jpeg-file-path))
    (delete-file (jpeg-file-path)))
  (when (probe-file (svg-file-path))
    (delete-file (svg-file-path))))

(defun paintr ()
  "Do everything."
  (load-current-id)
  (let ((palxml (colr-random-palette)))
    (when palxml
      (let ((palname (palette-name palxml))
	    (paltags (palette-tags palxml))
	    (palcolours (palette-colours palxml)))
	(when (and palname paltags palcolours)
	  (let ((photoxml (flickr-photo-tag-search paltags)))
	    (when photoxml
	      (let ((photoname (photo-title photoxml))
		    (photojpegurl (photo-jpeg-url photoxml))
		    (photoid (photo-id photoxml))
		    (photouserid (photo-owner-id photoxml)))
		(when (and photoname photojpegurl photouserid)
		  (let ((photouserxml (flickr-person-details photouserid)))
		    (when photouserxml
		      (let ((photousername (person-username photouserxml)))
			(wget photojpegurl (jpeg-file-path))
			(autotrace (jpeg-file-path) 
				   (svg-file-path) 
				   (length palcolours))
			(let ((svg-text nil))
			  (with-open-file (file (svg-file-path))
			    (setf svg-text (make-string (file-length file)))
			    (read-sequence svg-text file))
			  (setf svg-text (fix-svg svg-text))
			  (setf svg-text (recolour-svg svg-text palcolours))
			  (with-open-file (file (svg-file-path) 
						:direction :output 
						:if-exists :supersede)
			    (write-sequence svg-text file)))
			(gzip (svg-file-path))
			(rename-file (svg-gz-file-path) (svgz-file-path))
			(save-writeup (description-file-path) palname paltags 
				      (photo-html-url photoid photouserxml) 
				      photoname photousername))
		      (save-current-id)))))))))))
  (cleanup))

;;(paintr)
;;(quit)