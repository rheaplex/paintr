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

import java.io.*;
import java.util.*;
import java.util.regex.*;

import java.awt.*;
import java.awt.geom.*;

import java.applet.*;

// Scale the svg to fit in the Applet?
// Applet size will have been set proportionally by the php

public class paintr_applet
    extends Applet
    implements Runnable
{
    paintr picture = null;

    static final int transparency_thread_sleep = 50;
    protected Thread transparency_thread = null;

    // Double buffering
    private Dimension offscreen_dimensions = null;
    private Image offscreen_image = null;
    protected Graphics2D offscreen_graphics = null;
       
    // Thread (runnable) method
    public void run() 
    {
	while (Thread.currentThread () == transparency_thread) 
	    {
		boolean finished = picture.update_paths_transparency ();
		repaint ();
		if (finished)
		    break;
		try
		    {
			Thread.sleep (transparency_thread_sleep);
		    }
		catch (InterruptedException e)
		    {}
	    }
    }

    // Applet start method
    public void start ()
    {
	//Start animating!
        if (transparency_thread == null)
            transparency_thread = new Thread (this);
        transparency_thread.start();
    }

    // Applet stop method

    public void stop ()
    {
	transparency_thread = null;
    }
    
    // Applet update method, overridden to implement double-bufffering

    public void update(Graphics g) 
    {
        Dimension d = getSize();

        // Create the offscreen graphics context
        if ((offscreen_graphics == null)
         || (d.width != offscreen_dimensions.width)
         || (d.height != offscreen_dimensions.height)) 
	    {
		offscreen_dimensions = d;
		offscreen_image = createImage (d.width, d.height);
		offscreen_graphics = (Graphics2D)offscreen_image.getGraphics ();
	    }

        offscreen_graphics.setBackground (Color.WHITE);
        offscreen_graphics.clearRect(0, 0, d.width, d.height);
        offscreen_graphics.setRenderingHint(RenderingHints.KEY_ANTIALIASING,
					    RenderingHints.VALUE_ANTIALIAS_ON);

        // Paint the frame into the image
        picture.paint_paths (offscreen_graphics);

        // Paint the image onto the screen
        g.drawImage (offscreen_image, 0, 0, null);
    }

    // Applet paint method, overridden to implement double-buffering

    public void paint(Graphics g) 
    {
        if (offscreen_image != null) 
	    {
		g.drawImage (offscreen_image, 0, 0, null);
	    }
    }

    public void init ()
    {
	String svg_url = getParameter ("url");
	picture = new paintr (svg_url, getWidth (), getHeight ());
	// Animation thread will be spawned by start () method
    }
}