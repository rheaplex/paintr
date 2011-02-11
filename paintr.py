# paintr.py -  Art in the age of network services (Version 3).
# Copyright (C) 2009-2011  Rob Myers rob@robmyers.org
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU Affero General Public License as
# published by the Free Software Foundation, either version 3 of the
# License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU Affero General Public License for more details.
#
# You should have received a copy of the GNU Affero General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.

################################################################################
## Imports
################################################################################

import feedparser
import flickrapi
import sqlite3
import subprocess

################################################################################
## Configuration
################################################################################

PAINTR_DIRECTORY_PATH = "./"
FLICKR_API_KEY ="98170ee24764224d926092360a20da8f"
CURRENT_ID="current id"

################################################################################
## Call local command line tools
################################################################################

def autotrace(input_file_path, output_file_path, colour_count):
    """Convert the input file to svg with colour-count colours"""
    subprocess.call(["autotrace", "--output-format", "svg",
                     "--output-file", output_file_path,
                     "--color-count", colour_count,
                     "--despeckle-level",  "10",
                     input_file_path])

def wget(remote_url, local_file_path):
    """Get the remote url to a local file"""
    subprocess.call(["wget", "-O", local_file_path, remote_url])

def gzip(file_path):
  """Gzip the file and give it a specific suffix, svgz for example"""
   subprocess.call(["gzip", file_path])

################################################################################
## Database
################################################################################

def create_database_structure(connection):
    """Create the database structure"""
    connection.execute("create table state(key, value)")

def database_exists_structure(connection):
    """Check whether the database structure exists"""
    return .execute("select name from sqlite_master where type='table'"
                    ).fetchone()

def open_database():
    """Open database connection, creating tables if needed"""
    connection = sqlite3.connect(DATABASE_PATH)
    connection.isolation_level = None
    # Create the table if it doesn't exist
    if not database_structure_exists(connection):
        create_database_structure(connection)
        logging.info("CREATING DATABASE!")
    return connection

def close_database(connection):
    """Close database connection"""
    connection.close()

def store_state(connection, key, value):
    """Store the state in the database"""
     if connection.execute("select * from state where key=?",(key,)).fetchone():
        connection.execute("update state set value=? where key=?", (key, value))
    else:
        connection.execute("insert into state values(?, ?)", (key, value))

def retrieve_state(connection, key):
    """Get the state from the database"""
    connection.execute("select * from state where key=?",(key,)).fetchone()

################################################################################
## Current ID
################################################################################

def retrieve_new_current_id(connection):
    """Get the new current id"""
    previous = retrieve_state(connection, CURRENT_ID) or 0
    return previous + 1

def save_current_id(connection, current_id):
    """Store the current id"""
    store_state(connection, CURRENT_ID, current_id)

################################################################################
## Colr
################################################################################

def colr_random_palette():
    """Get the first palette from the colr rss"""
    feed = feedparser.parse("http://colr.org/rss/scheme/random")
    return feed.items[0]
  
def palette_name(palette):
    """Get the name from the palette XML"""
    return palette.title

def palette_tags(palette):
    """Get the tags from the palette XML"""
    return palette.tags.split()

def palette_colours(palette):
    """Get the colours as strings from the palette XML"""
    return palette.colors.split()

################################################################################
## Flickr
################################################################################

def flickr_photo_tag_search(flickr_api, tags):
  """Fetch a single BY-SA photo matching the tags, or None"""
  resultSet = flickr_api.photos_search(tags=tags, license=5, per_page=1)
  result = None
  if resultSet != []:
      result == resultSet[0]
  return result


def photo_jpeg_url(photo):
    """Construct the default jpeg url for a photo"""
    return "http://farm%s.static.flickr.com/%s/%s_%s.jpg" % (photo.farm_id,
                                                             photo.server_id,
                                                             photo.photo_id,
                                                             photo.secret_id)

def photo_page_url(photo)
  "Construct the page url for a photo"
  return "http://www.flickr.com/photos/~a/~a" % (photo.owner_id, photo.photo_id)

defun flickr_person_details(flickr_api, user_id):
  """Get the details from flickr for the given person id"""
  return flickr_api.people_getInfo(user_id=user_id)

def photo_html_url (photo, person)
  """Construct the html url for a photo"""
  return "%s%s/" % (person.photosurl, photo.photo_id)

# NOTE: Other properties to be accessed directly on objects





flickr_api = flickrapi.FlickrAPI(FLICKR_API_KEY)
