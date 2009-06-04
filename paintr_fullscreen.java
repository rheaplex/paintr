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

import java.awt.*;
import java.awt.event.*;
import java.awt.image.*;
import java.io.*;
import java.net.*;
import java.util.*;
import java.util.regex.*;

//import com.apple.cocoa.application.*;


public class paintr_fullscreen {

	private static final int credit_size = 8;
    private static Pattern credit_pattern = 
		Pattern.compile ("</a> by (.+) which ");
 	private String credit = "";
	private static final int credit_alpha_step = 5;
 	private int credit_current_alpha = 0;
	
	private paintr_series series = null;
	
	private void initialise_series (String url, int min, int max) {
		series = new paintr_series (url, min, max);
	}
	
	private String get_url (String url_path) {
		String html = "";
		try {
			URL url = new URL (url_path);
			BufferedReader in = new BufferedReader(new InputStreamReader(url.openStream()));
			String str;
			while ((str = in.readLine()) != null) {
				html += str;	
			}
		} catch (Exception e)
		{}
			return html;
	 } 
	 
	private int get_max_id () {
		String id_string = get_url ("http://paintr.robmyers.org/current.id");
		return Integer.parseInt (id_string);
	}
	
	private void next_credit ()
	{
		credit = "By paintr and anonymous";
		String html = get_url (series.base_url () + series.current_id () + ".html");
		credit_current_alpha = 255;
		try {
			Matcher matcher = credit_pattern.matcher (html);
			if (matcher.find ()) {
				String name = matcher.group (1);
				if (name.equals (""))
					name = "anonymous";
				credit = "By paintr and " + name;
			}
		} catch (Exception e)
		{}
	}
	
	private void update_credit_transparency () {
		if (credit_current_alpha > 0) {
			credit_current_alpha = credit_current_alpha - credit_alpha_step;
		}
	}
	
	private paintr update_paintr (paintr current, int width, int height) {
		if (current == null) {
			next_credit ();
			return series.next (width, height);
		}
		update_credit_transparency ();
		boolean finished = current.update_paths_transparency ();
		if (finished)
		{
			try {
				Thread.sleep (30 * 1000);
			} catch (InterruptedException e) {}
			next_credit ();
			return series.next (width, height);
		}
		return current;
	}
	
    public void rendering_loop (Window win, BufferStrategy strategy, 
								BufferCapabilities.FlipContents flipContents) {
		paintr picture = null;
        while (true) {
            // Get screen size
            int screenWidth = win.getWidth ();
            int screenHeight = win.getHeight ();
			picture = update_paintr (picture, screenWidth, screenHeight);
            // Get graphics context for drawing to the window
            Graphics g = strategy.getDrawGraphics ();
    
			// Clear background
            if ( ! flipContents.equals (BufferCapabilities.
					FlipContents.BACKGROUND)) {
				g.setColor (Color.white);
				g.fillRect (0, 0, screenWidth, screenHeight);
			}
    
            // Draw 
			Graphics2D g2 = (Graphics2D)g;
			g2.setRenderingHint(RenderingHints.KEY_ANTIALIASING,
					    RenderingHints.VALUE_ANTIALIAS_ON);
			picture.paint_paths (g2);
			
			Color col = new Color (0, 0, 0, credit_current_alpha);
			g.setColor(col);
			g.drawString (credit, credit_size * 8, credit_size * 2);
	
            // Done drawing
            g.dispose ();
    
            // Flip the back buffer to the screen
            strategy.show ();
        }
    }

    public void run_fullscreen (GraphicsDevice gd, Window win)
    {
		try {
			// Enter full-screen mode
			gd.setFullScreenWindow (win);
			win.requestFocus ();
		
			// Create the back buffer
			int numBuffers = 2;  // Includes front buffer
			win.createBufferStrategy (numBuffers);
		
			BufferStrategy strategy = win.getBufferStrategy ();
			BufferCapabilities bufCap = strategy.getCapabilities ();
			BufferCapabilities.FlipContents flipContents = 
			bufCap.getFlipContents ();
		   
			rendering_loop (win, strategy, flipContents);
		} catch (Throwable e) {
		} finally {
			gd.setFullScreenWindow (null);
		}
    }

    private void add_exit_mouseclick_handler (Window win)
    {
		win.addMouseListener (new MouseAdapter () {
			public void mousePressed(MouseEvent evt) {
				// Exit full-screen mode
				GraphicsDevice gd = 
				GraphicsEnvironment.getLocalGraphicsEnvironment ()
				.getDefaultScreenDevice ();
				gd.setFullScreenWindow (null);
				System.exit (0);
			}
			});
    }
	
	public paintr_fullscreen (String[] args) {
		series = new paintr_series ("http://paintr.robmyers.org/", 1, get_max_id ());
	}

	public void go () {
		GraphicsEnvironment ge = 
			GraphicsEnvironment.getLocalGraphicsEnvironment ();
		GraphicsDevice gd = ge.getDefaultScreenDevice ();
		Frame frame = new Frame (gd.getDefaultConfiguration ());
			
		//NSCursor.hide();
			
		Window win = new Window (frame);
		add_exit_mouseclick_handler (win);
		run_fullscreen (gd, win);
	}

    public static void main (String[] args)
    {
		paintr_fullscreen pfs = new paintr_fullscreen (args);
		pfs.go ();
    }

}