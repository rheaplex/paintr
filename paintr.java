// Copyright 2005 Rob Myers <rob@robmyers.org>
//     
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU Affero General Public License as
// published by the Free Software Foundation, either version 3 of the
// License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Affero General Public License for more details.
//
// You should have received a copy of the GNU Affero General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.

import javax.xml.parsers.*;
import org.xml.sax.*;  
import org.w3c.dom.*;

import java.io.*;
import java.util.*;
import java.util.regex.*;

import java.awt.*;
import java.awt.geom.*;

// Scale the svg to fit in the Applet?
// Applet size will have been set proportionally by the php

public class paintr
{
    // The SVG colour regex 
    private static Pattern colour_pattern = 
	Pattern.compile ("fill:#(..)(..)(..)");

    // The paths from the svg
    protected Vector svg_paths = null;
    protected Vector svg_colors = null;
    // flickr photos are 240px on their longest side
    protected float scale_factor = 540.0f / 240.0f;

    static final int transparency_step = 4;
    private int transparency_current_path = 0;
    private int transparency_current_alpha = 0;
	    
    public void reset_transparency ()
    {
        transparency_current_path = 0;
    	transparency_current_alpha = 0;
    }

    protected Document load_xml (String file_url)
    {
		Document document = null;
		DocumentBuilderFactory factory =
            DocumentBuilderFactory.newInstance();
        try {
	    DocumentBuilder builder = factory.newDocumentBuilder();
	    document = builder.parse( file_url );
        } catch (SAXException sxe) {
	    Exception  x = sxe;
	    if (sxe.getException() != null)
		x = sxe.getException();
	    x.printStackTrace();
	    
        } catch (ParserConfigurationException pce) {
            // Parser with specified options can't be built
            pce.printStackTrace();
	    
        } catch (IOException ioe) {
	    // I/O error
	    ioe.printStackTrace();
        }

		return document;
    }

    protected void parse_xml (Document document)
    {
    	svg_paths = new Vector ();
   		svg_colors = new Vector ();
   		
		NodeList elements = document.getElementsByTagName ("path");
		int count = elements.getLength();
        for (int i=0; i < count; i++) 
	    {
			Element element = (Element)elements.item (i);
			String style = element.getAttribute ("style");
			String path = element.getAttribute ("d");
			parse_path (style, path);
	    }
    }

    private Color parse_path_style (String style)
    {
		Color color = new Color (255, 255, 255);
        Matcher matcher = colour_pattern.matcher (style);
		if (matcher.find ())
	    {
			String r_string =matcher.group (1);
			String g_string = matcher.group (2);
			String b_string = matcher.group (3);
			int r = Integer.parseInt (r_string, 16);
			int g = Integer.parseInt (g_string, 16);
			int b = Integer.parseInt (b_string, 16);
			color = new Color (r, g, b, 0);
	    }
		return color;
    } 

    // Parse a float and scale the co-ordinate

    private float next_float (StringTokenizer arguments)
    {
	return Float.parseFloat (arguments.nextToken ())
	    * scale_factor;
    }

    private GeneralPath parse_path_d (String path)
    {
        GeneralPath p = new GeneralPath(GeneralPath.WIND_EVEN_ODD);
		StringTokenizer tokens = new StringTokenizer (path, "MLCz", true);
		while (tokens.hasMoreTokens ())
			{
			String operator = tokens.nextToken ();
			if (operator.equals ("z"))
				{
				break;
				}
			if (operator.equals ("M"))
				{
				StringTokenizer arguments = 
					new StringTokenizer (tokens.nextToken (), " ");
				p.moveTo (next_float (arguments), 
					  next_float (arguments));
				}
			if (operator.equals ("L"))
				{
				StringTokenizer arguments = 
					new StringTokenizer (tokens.nextToken (), " ");
				p.lineTo (next_float (arguments), 
					  next_float (arguments));
				}
			if (operator.equals ("C"))
				{
				StringTokenizer arguments = 
					new StringTokenizer (tokens.nextToken (), " ");
				p.curveTo (next_float (arguments), 
					   next_float (arguments),
					   next_float (arguments), 
					   next_float (arguments),
					   next_float (arguments), 
					   next_float (arguments));
				}
			}
		return p;
    }

    private void parse_path (String style, String d)
    {
		GeneralPath p = parse_path_d (d);
		svg_paths.add (p);
		Color c = parse_path_style (style);
		svg_colors.add (c);
    }

    public void paint_paths (Graphics2D g2)
    {
		int count = svg_paths.size ();
		for (int i = 0; i < count; i++)
	    {
			Color c = (Color)svg_colors.elementAt (i);
			g2.setPaint (c);
			Shape p = (Shape)svg_paths.elementAt (i);
			g2.fill (p);
	    }
    }

    // Use a GradientPaint from col -> col + alpha, offset scaled to 2
    // So it ends up all solid

    public boolean update_paths_transparency ()
    {
		// Increase the opacity of the current path
		transparency_current_alpha += transparency_step;
		// If it's fully opaque, move on to the next path
		if (transparency_current_alpha > 255)
			{
			transparency_current_alpha = transparency_step;
			transparency_current_path += 1;
			}
		// If we've run out of paths, finish
		if (transparency_current_path >= svg_colors.size ())
			return true;
		// Otherwise set the path's transparency
		Color c = (Color)svg_colors.elementAt (transparency_current_path);
		Color new_color = new Color (c.getRed (), 
						 c.getGreen (), 
						 c.getBlue (), 
						 transparency_current_alpha);
		svg_colors.setElementAt (new_color, transparency_current_path);
		return false;
    }

    paintr (String svg_url, int width, int height)
    {
		scale_factor = width / 240.0f;
		Document document = load_xml (svg_url);
		parse_xml (document);
    }
}