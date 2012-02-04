<script type="text/javascript">
	/**
	 * Backbone.js wiring for the droplets MVC
	 */
	$(function() {
		// Models for the droplet places, tags and links 
		window.DropletPlace = Backbone.Model.extend();
		window.DropletTag = Backbone.Model.extend();
		window.DropletLink = Backbone.Model.extend();
		window.Bucket = Backbone.Model.extend();
		
		// Collections for droplet places, tags and links
		window.DropletPlaceCollection = Backbone.Collection.extend({
			model: DropletPlace
		})
	
		window.DropletTagCollection = Backbone.Collection.extend({
			model: DropletTag
		});
	
		window.DropletLinkCollection = Backbone.Collection.extend({
			model: DropletLink
		});
		
		// Collection for the buckets a droplet a droplet belongs to
		window.DropletBucketsCollection = Backbone.Collection.extend({
			model: Bucket
		});
		
		// Collection for all the buckets accessible to the current user
		window.BucketList = Backbone.Collection.extend({
			model: Bucket,
			url: "<?php echo url::site("/bucket/list_buckets"); ?>"
		});		
		
	
		// Droplet model
		window.Droplet = Backbone.Model.extend({

			setBucket: function(changeBucket) {
			    // Is this droplet already in the bucket?
			    change_buckets = this.get("buckets");
			    if(_.any(change_buckets, function(bucket) { return bucket["id"] == changeBucket.get("id"); })) {
			        // Remove the bucket from the list
			        change_buckets = _.filter(change_buckets, function(bucket) { return bucket["id"] != changeBucket.get("id"); });
			        this.set('buckets', change_buckets);
			    } else {
			        change_buckets.push({id: changeBucket.get("id"), bucket_name: changeBucket.get("bucket_name")});
			    }
			    this.save({buckets: change_buckets}, {wait: true});
			}
		});
	
		// Droplet & Bucket collection
		window.DropletList = Backbone.Collection.extend({
			model: Droplet,
			url: "<?php echo $fetch_url; ?>"
		});
		
		window.Droplets = new DropletList;
		window.bucketList = new BucketList()
	
				
		// Rendering for a single droplet in the list view
		window.DropletView = Backbone.View.extend({
		
			tagName: "article",
		
			className: "item",
		
			template: _.template($("#droplet-template").html()),
			
			events: {
				// Show the droplet detail
				"click .button-view a.detail-view": "showDetail",
				"click div.bottom a.close": "showDetail",
				
				// Show the list of buckets available to the current user
				"click .bucket a.bucket-view": "showBuckets",				
				"click .bucket .dropdown p.create-new a": "showCreateBucket",
				"click div.create-name button.cancel": "cancelCreateBucket",
				"click div.create-name button.save": "saveBucket"
			},
						
			initialize: function() {
			    bucketList.on('add', this.addBucket, this); 
			    
				// List of general tags for the droplet
				this.tagList = new DropletTagCollection();
				this.tagList.on('reset', this.addTags, this);
				
				// List of links in a droplet
				this.linkList = new DropletLinkCollection();
				this.linkList.on('reset', this.addLinks, this); 
				 
				// List of links in a droplet
				this.placeList = new DropletPlaceCollection();
				this.placeList.on('reset', this.addPlaces, this); 
								
			},
			
			render: function(eventName) {
				$(this.el).attr("data-droplet-id", this.model.get("id"));
				$(this.el).html(this.template(this.model.toJSON()));
				this.tagList.reset(this.model.get('tags'));
				this.linkList.reset(this.model.get('links'));
				this.placeList.reset(this.model.get('places'));
				return this;
			},
			
			addTag: function(tag) {
			    var view = new TagView({model: tag});
			    this.$("ul.tags").append(view.render().el);
			},
						
			addTags: function() {
			    this.tagList.each(this.addTag, this);
			},

			addLink: function(link) {
			    var view = new LinkView({model: link});
			    this.$("div.links").append(view.render().el);
			},
						
			addLinks: function() {
			    this.linkList.each(this.addLink, this);
			},

			addPlace: function(place) {
			    var view = new PlaceView({model: place});
			    this.$("div.locations").append(view.render().el);
			},
						
			addPlaces: function() {
			    this.placeList.each(this.addPlace, this);
			},
						
			addBucket: function() {
			    this.createBucketMenu();			    
			},
			
			// Creates the buckets dropdown menu
			createBucketMenu: function() {			    
				var dropdown = this.$(".actions .bucket div.dropdown .buckets-list ul");
				dropdown.empty();				
				var droplet = this.model
				
				// Render the buckets
				bucketList.each(function (bucket) {
				    // Attach the droplet's buckets' to the model
    				// Buckets this droplet belongs to will appear selected
                    bucket.set('droplet_buckets', droplet.get('buckets'));
                    bucket.set('droplet_id', droplet.get('id'));
                    
				    var bucketView = new BucketView({model: bucket});
				    dropdown.append(bucketView.render().el);
				});
			},
		
			// Event callback for the "view more detail" action
			showDetail: function() {			    
				var button = this.$(".button-view a.detail-view");
			
				// Display the droplet detail
				if (button.hasClass("detail-hide")) {
                    button.removeClass('detail-hide')
                          .closest('article')
                          .children('div.drawer')
                          .slideUp('slow');
				} else {
                    button.addClass('detail-hide')
                          .closest('article')
                          .children('div.drawer')
                          .slideDown('slow');
				}			
			},
			
			// Event callback for the "Add to Bucket" action
			showBuckets: function(e) {
				var parentEl = $(e.currentTarget).parent("p");
				parentEl.toggleClass("active");
				
				var dropdown = $("div.dropdown", parentEl.parent("div"));
				
				if (parentEl.hasClass("active")) {					
					this.createBucketMenu();
					dropdown.show();
				} else {
					dropdown.hide();
				}
				return false;
			},
			
			// Event callback for the "create & add to a new bucket" action
			showCreateBucket: function(e) {
			    this.$("div.dropdown div.create-name").fadeIn();
			    this.$("div.dropdown p.create-new a.plus").fadeOut();
			},
			
			// When cancel buttons is clicked in the add bucket drop down
			cancelCreateBucket: function (e) {
			    this.$("div.dropdown div.create-name").fadeOut();
			    this.$("div.dropdown p.create-new a.plus").fadeIn();
			    e.stopPropagation();
			},
			
			// When save button is clicked in the add bucket drop down
			saveBucket: function(e) {
                if(this.$(":text").val()) {
                    bucketList.create({bucket_name: this.$(":text").val()}, {wait: true});
                    this.$(":text").val('');
                }                    
            	this.$("div.dropdown div.create-name").fadeOut();
            	this.$("div.dropdown p.create-new a.plus").fadeIn();
            	e.stopPropagation();
			}
			
		});
		
		// The droplest list
		window.DropletListView = Backbone.View.extend({
		
			el: $("#droplet-list"),
					
			initialize: function() {
				Droplets.on('add',   this.addDroplet, this);
                Droplets.on('reset', this.addDroplets, this); 
			},
			
			addDroplet: function(droplet) {
			    var view = new DropletView({model: droplet});
			    
			    // Recent items populate at the top othewise append
			    if(maxId && droplet.get('id') > maxId) {
			        this.$el.prepend(view.render().el);
			    } else {
			        this.$el.append(view.render().el);			        
			    }			    
			},
			
			addDroplets: function() {
			    Droplets.each(this.addDroplet, this);
			},		
		});
		
		// View for an individual tag
		window.TagView = Backbone.View.extend({
			
			tagName: "li",
									
			template: _.template($("#tag-template").html()),
			
			render: function() {
				$(this.el).html(this.template(this.model.toJSON()));
				return this;
			},
			
		});

		// View for an individual link
		window.LinkView = Backbone.View.extend({
			
			tagName: "p",
									
			template: _.template($("#link-template").html()),
			
			render: function() {
				$(this.el).html(this.template(this.model.toJSON()));
				return this;
			},
			
		});

		// View for an individual place
		window.PlaceView = Backbone.View.extend({
			
			tagName: "p",
									
			template: _.template($("#place-template").html()),
			
			render: function() {
				$(this.el).html(this.template(this.model.toJSON()));
				return this;
			},
			
		});
			
		
		// View for individual bucket item in a droplet list dropdown
		window.BucketView = Backbone.View.extend({
			
			tagName: "li",
			
			className: "checkbox",
			
			template: _.template($("#bucket-template").html()),
			
			events: {
				"click li.checkbox a": "toggleDropletMembership"
			},
						
			render: function() {
				$(this.el).html(this.template(this.model.toJSON()));
				return this;
			},
			
			// Toggles the bucket membership of a droplet
			toggleDropletMembership: function(e) {
			    droplet = Droplets.get(this.model.get('droplet_id'));
			    droplet.setBucket(this.model);
			    this.$("li.checkbox a").toggleClass("selected");
			}
		});
		
		// Load content while scrolling - Infinite Scrolling
		var pageNo = 1;
		var maxId = 0;
		$(window).scroll(function() {
			if ($(window).scrollTop() == ($(document).height() - $(window).height())) {
				// Next page
				pageNo += 1;
							
				// Fetch content and update the view
				Droplets.fetch({data: {page: pageNo, max_id: maxId}, add: true});
			}
		});
		
		// Poll for new droplets
		var sinceId = 0
		function updateDropletView() {
		    
		    // Get the max id we have
		    Droplets.each(function(droplet) { 
		        if (parseInt(droplet.get("id")) > sinceId) { 
		            sinceId = parseInt(droplet.get("id"));
		        }
		    });
		    
		    if(sinceId) {		        
		        Droplets.fetch({data: {since_id: sinceId}, add: true});
		    }
		}
		// Update the droplet view every 30 seconds
        setInterval(updateDropletView, 30000);
				
		// Bootstrap the droplet list
		window.dropletList = new DropletListView;
		Droplets.reset(<?php echo $droplet_list ?>);		
		bucketList.reset(<?php echo $bucket_list ?>);
		
		// Pagination reference point
		// set the max id we have the first time only
		if ( ! maxId) {
		    Droplets.each(function(droplet) { 
		        if (parseInt(droplet.get("id")) > maxId) { 
		            maxId = parseInt(droplet.get("id"))
		        }
		    });
		}
				
	});
</script>