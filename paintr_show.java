// Copyright 2005 Rob Myers <rob@robmyers.org>    
//     
// This file is part of paintr.
// 
// paintr is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 3 of the License, or
// (at your option) any later version.
// 
// paintr is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// 
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.

import javax.xml.parsers.*;
import org.xml.sax.*;  
import org.w3c.dom.*;

import java.net.*;
import java.io.*;
import java.util.*;
import java.util.regex.*;

import java.awt.*;
import java.awt.geom.*;

import java.applet.*;

public class paintr_show
    extends paintr
    implements Runnable
{
	public static final int credit_size = 8;

    private static Pattern credit_pattern = 
		Pattern.compile ("</a> by (.+) which ");
	
 	private String base_url = "";
 	
 	private String credit = "";
 	private int credit_current_alpha = 0;
 	
 	private int start_id = 0;
 	private int min_id = 0;
 	private int current_id = 0;
 	
    private void load_next_document ()
    {
    	String svg_url = base_url + current_id + ".svg";
		Document document = load_xml (svg_url);
		parse_xml (document);
    }
    
	private void get_credit ()
	{
		credit = "";
		try
		{
			URL html_url = new URL (base_url + current_id + ".html");
			String html = html_url.getContent ().toString ();
			Matcher matcher = credit_pattern.matcher (html);
			if (matcher.find ())	
			{
				credit = matcher.group (1);
			}
		}
		catch (Exception e)
		{}
	}
	
	private void next_id ()
	{
		current_id = current_id - 1;
		if(current_id < min_id)
			current_id = start_id;
	}
	
 	protected void next_picture ()
 	{
		reset_transparency ();
		credit_current_alpha = 255;
 		load_next_document ();
		get_credit ();
		next_id ();
 	}
 
    // Thread (runnable) method
    public void run() 
    {
		while (Thread.currentThread () == transparency_thread) 
	    {
			boolean finished = update_paths_transparency ();
			if (credit_current_alpha > 0)
				credit_current_alpha --;
			repaint ();
			if (finished)
			{
				try
				{
					Thread.sleep (1000 * 30);
				}
				catch (InterruptedException e)
		    	{}
				next_picture ();
			}
			try
			{
				Thread.sleep (transparency_thread_sleep);
			}
			catch (InterruptedException e)
		    {}
	    }
    }
    
    public void update(Graphics g) 
    {
    	super.update (g);
    	
    	Graphics2D g2 = (Graphics2D)g;
    
       	Color c = new Color (128, 128, 128, credit_current_alpha);
		offscreen_graphics.setPaint (c);
    	Font f = new Font("Helvetica", Font.PLAIN, credit_size);
   		offscreen_graphics.setFont (f);
   		offscreen_graphics.drawString (credit, 4, credit_size);
   }

    public void init ()
    {
	    scale_factor = ((float)getWidth ()) / 240.0f;
    	base_url = getParameter ("url");
		start_id = Integer.parseInt (getParameter ("id"));
		current_id = start_id;
		min_id = start_id - 60;	
		if (min_id < 1)
			min_id = 1;
    	next_picture ();
    }
    
 
}