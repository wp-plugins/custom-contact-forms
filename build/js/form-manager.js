( function( $, Backbone, _, ccfSettings ) {
	'use strict';

	window.wp = window.wp || {};
	wp.ccf = wp.ccf || {};
	wp.ccf.utils = wp.ccf.utils || {};

	wp.ccf.utils.cleanDateFields = function( object ) {
		delete object.date;
		delete object.date_gmt;
		delete object.modified;
		delete object.modified_gmt;
		delete object.date_tz;
		delete object.modified_tz;
	};

	wp.ccf.utils.insertFormShortcode = function( form ) {
		var existingForm = wp.ccf.forms.findWhere( { ID: form.get( 'ID' ) } );
		if ( ! existingForm ) {
			wp.ccf.forms.add( form );
		}

		var editor = tinymce.get( wpActiveEditor );
		var shortcode = '[ccf_form id="' + form.get( 'ID' ) + '"]';

		if ( editor && ! editor.isHidden() ) {
			tinymce.activeEditor.execCommand( 'mceInsertContent', false, shortcode );
		} else {
			document.getElementById( wpActiveEditor ).value += shortcode;
		}
	};

	wp.ccf.utils.getPrettyPostDate = function( date ) {
		date = moment( date );

		return date.format( 'h:mm a M/D/YYYY' );
	};

	wp.ccf.utils.wordChop = function( string, maxLength ) {
		var trimmedString = string.substr( 0, maxLength );
		trimmedString.substr( 0, Math.min( trimmedString.length, trimmedString.lastIndexOf( ' ' ) ) );

		if ( trimmedString.length < string.length ) {
			trimmedString += '...';
		}

		return trimmedString;
	};

	wp.ccf.utils.isFieldDate = function( value ) {
		if ( typeof value.date !== 'undefined' || ( typeof value.hour !== 'undefined' && typeof value.minute !== 'undefined' && typeof value['am-pm'] !== 'undefined' ) ) {
			return true;
		}

		return false;
	};

	wp.ccf.utils.isFieldName = function( value ) {
		if ( typeof value.name !== 'undefined' || typeof value.last !== 'undefined' ) {
			return true;
		}

		return false;
	};

	wp.ccf.utils.isFieldAddress = function( value ) {
		if ( typeof value.street !== 'undefined' && typeof value.city !== 'undefined' && typeof value.zipcode !== 'undefined' && typeof value.line_two !== 'undefined' ) {
			return true;
		}

		return false;
	};

	wp.ccf.utils.getPrettyFieldDate = function( value ) {
		var dateString = '',
			output = '';

		if ( value.hour && value.minute && value['am-pm'] ) {
			dateString += value.hour + ':' + value.minute + ' ' + value['am-pm'];
		}

		if ( value.date ) {
			dateString += ' ' + value.date;
		}

		if ( ! dateString ) {
			return '-';
		}

		var date = moment( dateString );

		if ( ! date.isValid() ) {
			return ccfSettings.invalidDate;
		}

		if ( value.hour && value.minute && value['am-pm'] ) {
			output += date.format( 'h:mm a' );
		}

		if ( value.date ) {
			if ( output ) {
				output += ' ';
			}

			output += date.format( 'M/D/YYYY' );
		}

		return output;
	};

	wp.ccf.utils.getPrettyFieldName = function( value ) {
		var nameString = value.first;

		if ( nameString.length > 0 ) {
			nameString += ' ';
		}

		if ( value.last ) {
			nameString += value.last;
		}

		if ( ! nameString ) {
			nameString = '-';
		}

		return nameString;
	};

	wp.ccf.utils.getPrettyFieldAddress = function( value ) {
		if ( ! value.street || ! value.city ) {
			return '-';
		}

		var addressString = value.street;

		if ( value.line_two ) {
			addressString += ' ' + value.line_two;
		}

		addressString += ', ' + value.city;

		if ( value.state ) {
			addressString += ', ' + value.state;
		}

		if ( value.zipcode ) {
			addressString += ' ' + value.zipcode;
		}

		if ( value.country ) {
			addressString += ' ' + value.country;
		}

		return addressString;
	};

})( jQuery, Backbone, _, ccfSettings );
( function( $, Backbone, _, ccfSettings ) {
	'use strict';

	wp.ccf.mixins = wp.ccf.mixins || {};

	wp.ccf.mixins.subViewable = wp.ccf.mixins.subViewable || {
		subViews: {},

		initRenderSubViews: function( showAll, forceInit, args ) {
			if ( ! this.renderedSubViews ) {
				this.renderedSubViews = {};
			}

			for ( var id in this.subViews ) {
				var context = {
					el: this.$el.find( '.ccf-' + id ),
					parent: this
				};

				if ( args ) {
					_.extend( context, args );
				}

				if ( this.renderedSubViews[id] && this.renderedSubViews[id].destroy ) {
					this.renderedSubViews[id].destroy();
				}

				if ( forceInit || ! this.renderedSubViews[id] ) {
					this.renderedSubViews[id] = new this.subViews[id]( context );
				}

				this.renderedSubViews[id].render();

				if ( showAll ) {
					this.renderedSubViews[id].el.style.display = 'block';
				}
			}

			return this;
		},

		showView: function( id, options, noRender ) {
			if ( typeof this.renderedSubViews !== 'undefined' && typeof this.renderedSubViews[id] !== 'undefined' ) {
				var view = this.renderedSubViews[id];
				if ( ! noRender ) {
					view.render( options );
				}

				view.el.style.display = 'block';
				this.currentView = id;

				for ( var viewId in this.subViews ) {
					if ( viewId !== id ) {
						this.renderedSubViews[viewId].el.style.display = 'none';
					}
				}
			}
		}
	};
})( jQuery, Backbone, _, ccfSettings );
( function( $, Backbone, _, ccfSettings, WP_API_Settings ) {
	'use strict';

	wp.ccf.models = wp.ccf.models || {};
	wp.ccf.models.Fields = wp.ccf.models.Fields || {};

	/**
	 * A terrible ie8 polyfill to fix JSON.stringify issues and empty strings pulled
	 * straight from the DOM.
 	 */

	var _modelSet = function( key, value, options ) {
		if ( typeof value !== 'object' && value === '' ) {
			value = '';
		}

		return Backbone.Model.prototype.set.call( this, key, value, options );
	};

	/**
	 * We decode HTML entities after syncing then escape on output. The
	 * point of this is to prevent double escaping.
	 */
	var _modelDecode = function( excludeKeys ) {
		for ( var key in this.attributes ) {
			if ( excludeKeys.indexOf( key ) === -1 ) {
				var value = this.get( key );

				if ( typeof value === 'string' && value !== '' ) {
					value = String( value )
						.replace( /&amp;/g, '&' )
						.replace( /&lt;/g, '<' )
						.replace( /&gt;/g, '>' )
						.replace( /&quot;/g, '"' )
						.replace( /&#8220;/g, '”' )
						.replace( /&#8221;/g, '”' )
						.replace( /&#8216;/g, "‘" )
						.replace( /&#039;/g, "'" );

					this.set( key, value );
				}
			}
		}

		return this;
	};

	wp.ccf.models.FieldChoice = wp.ccf.models.FieldChoice || Backbone.Model.extend(
		{
			defaults: {
				label: '',
				value: '',
				selected: false
			},

			decode: function() {
				return _modelDecode.call( this, [] );
			},

			set: _modelSet
		}
	);

	wp.ccf.models.Form = wp.ccf.models.Form || wp.api.models.Post.extend(
		{

			urlRoot: WP_API_Settings.root + '/ccf/forms',

			set: _modelSet,

			initialize: function() {
				this.on( 'sync', this.decode, this );
			},

			defaults: function() {
				var defaults = {
					fields: new wp.ccf.collections.Fields(),
					type: 'ccf_form',
					status: 'publish',
					description: '',
					buttonText: 'Submit Form',
					completionActionType: 'text',
					completionRedirectUrl: '',
					completionMessage: '',
					sendEmailNotifications: false,
					emailNotificationAddresses: ccfSettings.adminEmail
				};

				defaults = _.defaults( defaults, this.constructor.__super__.defaults );
				wp.ccf.utils.cleanDateFields( defaults );

				return defaults;
			},

			decode: function() {
				var keys = _.keys( wp.api.models.Post.prototype.defaults );
				keys = _.without( keys, 'title' );

				return _modelDecode.call( this, keys );
			},

			getFieldSlugs: function( mutableOnly ) {
				var fields = wp.ccf.currentForm.get( 'fields' ),
					columns = [];

				fields.each( function( field ) {
					if ( mutableOnly ) {
						if ( field.isImmutable ) {
							return;
						}
					}

					columns.push( field.get( 'slug' ) );
				});

				return columns;
			},

			parse: function( response ) {
				var SELF = this;

				if ( response.fields ) {

					var fields = SELF.get( 'fields' );

					if ( fields && fields.length > 0 ) {

						for ( var i = 0; i < response.fields.length; i++ ) {
							var newField = response.fields[i];

							var field = fields.findWhere( { slug: newField.slug } );

							if ( field ) {
								if ( typeof newField.choices !== 'undefined' ) {
									var choices = SELF.get( 'choices' );

									if ( choices && choices.length > 0 ) {
										for ( var z = 0; z < newField.choices; z++ ) {
											var choice = choices.at( z );
											choice.set( newField.choices[z] );
											choice.decode();
										}
									}

									delete response.fields[i].choices;
								}

								field.set( newField );
								field.decode();
							}
						}

						delete response.fields;
					} else {
						var newFields = [];

						_.each( response.fields, function( field ) {
							var fieldModel = new wp.ccf.models.Fields[field.type]( field );
							fieldModel.decode();

							newFields.push( fieldModel );
						});

						response.fields = new wp.ccf.collections.Fields( newFields, { formId: response.ID } );
					}
				}

				return this.constructor.__super__.parse.call( this, response );
			},

			toJSON: function() {
				var attributes = this.constructor.__super__.toJSON.call( this );

				if ( attributes.fields ) {
					attributes.fields = attributes.fields.toJSON();
				}

				if ( attributes.author ) {
					attributes.author = attributes.author.toJSON();
				}

				return attributes;
			}
		}
	);

	wp.ccf.models.Submission = wp.api.models.Submission || wp.api.models.Post.extend(
		{
			idAttribute: 'ID',

			defaults: {
				ID: null,
				data: {}
			}
		}
	);

	wp.ccf.models.Field = wp.api.models.Field || wp.api.models.Post.extend(
		{
			idAttribute: 'ID',

			defaults: {
				ID: null
			},

			set: _modelSet,

			required: function() {
				return [ 'slug' ];
			},

			decode: function() {
				return _modelDecode.call( this, _.keys( wp.api.models.Post.prototype.defaults ) );
			},

			hasRequiredAttributes: function() {
				var SELF = this;
				var reqsMet = true;

				_.each( this.required(), function( fieldSlug ) {
					if ( typeof SELF.get( fieldSlug ) === 'undefined' || SELF.get( fieldSlug ) === '' ) {
						reqsMet = false;
					}
				});

				return reqsMet;
			}
		}
	);

	wp.ccf.models.StandardField = wp.ccf.models.StandardField || wp.ccf.models.Field.extend(
		{
			idAttribute: 'ID',

			defaults: function() {
				var defaults = {
					label: 'Field Label',
					value: '',
					placeholder: '',
					slug: '',
					type: '',
					required: false,
					className: ''
				};

				return _.defaults( defaults, this.constructor.__super__.defaults );
			}
		}
	);

	wp.ccf.models.Fields['single-line-text'] = wp.ccf.models.Fields['single-line-text'] || wp.ccf.models.StandardField.extend(
		{
			defaults: function() {
				var defaults = {
					type: 'single-line-text'
				};

				return _.defaults( defaults, this.constructor.__super__.defaults() );
			}
		}
	);

	wp.ccf.models.Fields['paragraph-text'] = wp.ccf.models.Fields['paragraph-text'] || wp.ccf.models.StandardField.extend(
		{
			defaults: function() {
				var defaults = {
					type: 'paragraph-text'
				};

				return _.defaults( defaults, this.constructor.__super__.defaults() );
			}
		}
	);

	wp.ccf.models.Fields.hidden = wp.ccf.models.Fields.hidden || wp.ccf.models.StandardField.extend(
		{
			defaults: function() {
				var defaults = {
					type: 'hidden'
				};

				return _.defaults( defaults, this.constructor.__super__.defaults() );
			}
		}
	);

	wp.ccf.models.Fields.email = wp.ccf.models.Fields.email || wp.ccf.models.StandardField.extend(
		{
			defaults: function() {
				var defaults = {
					type: 'email',
					emailConfirmation: false
				};

				return _.defaults( defaults, this.constructor.__super__.defaults() );
			}
		}
	);

	wp.ccf.models.Fields.website = wp.ccf.models.Fields.website || wp.ccf.models.StandardField.extend(
		{
			defaults: function() {
				var defaults = {
					type: 'website'
				};

				return _.defaults( defaults, this.constructor.__super__.defaults() );
			}
		}
	);

	wp.ccf.models.Fields.phone = wp.ccf.models.Fields.phone || wp.ccf.models.StandardField.extend(
		{
			defaults: function() {
				var defaults = {
					type: 'phone',
					phoneFormat: 'us'
				};

				return _.defaults( defaults, this.constructor.__super__.defaults() );
			}
		}
	);

	wp.ccf.models.Fields.date = wp.ccf.models.Fields.date || wp.ccf.models.StandardField.extend(
		{
			defaults: function() {
				var defaults = {
					type: 'date',
					showDate: true,
					showTime: true
				};

				return _.defaults( defaults, this.constructor.__super__.defaults() );
			}
		}
	);

	wp.ccf.models.Fields.name = wp.ccf.models.Fields.name || wp.ccf.models.StandardField.extend(
		{
			defaults: function() {
				var defaults = {
					type: 'name'
				};

				return _.defaults( defaults, this.constructor.__super__.defaults() );
			}
		}
	);

	wp.ccf.models.Fields.recaptcha = wp.ccf.models.Fields.recaptcha || wp.ccf.models.StandardField.extend(
		{
			defaults: function() {
				var defaults = {
					type: 'recaptcha',
					siteKey: '',
					secretKey: ''
				};

				return _.defaults( defaults, this.constructor.__super__.defaults() );
			},

			required: function() {
				return [ 'siteKey', 'secretKey' ];
			},

			isImmutable: true
		}
	);

	wp.ccf.models.Fields.address = wp.ccf.models.Fields.address || wp.ccf.models.StandardField.extend(
		{
			defaults: function() {
				var defaults = {
					type: 'address',
					addressType: 'us'
				};

				return _.defaults( defaults, this.constructor.__super__.defaults() );
			}
		}
	);

	wp.ccf.models.Fields['section-header'] = wp.ccf.models.Fields['section-header'] || wp.ccf.models.Field.extend(
		{
			defaults: function() {
				var defaults = {
					type: 'section-header',
					slug: '',
					heading: '',
					subheading: '',
					className: ''
				};

				return _.defaults( defaults, this.constructor.__super__.defaults );
			},

			required: function() {
				return [];
			},

			isImmutable: true
		}
	);

	wp.ccf.models.Fields.html = wp.ccf.models.Fields.html || wp.ccf.models.Field.extend(
		{
			defaults: function() {
				var defaults = {
					type: 'html',
					slug: '',
					html: '',
					className: ''
				};

				return _.defaults( defaults, this.constructor.__super__.defaults );
			},

			required: function() {
				return [];
			},

			isImmutable: true
		}
	);

	wp.ccf.models.ChoiceableField = wp.ccf.models.ChoiceableField || wp.ccf.models.StandardField.extend(
		{
			defaults: function() {
				var defaults = {
					choices: new wp.ccf.collections.FieldChoices()
				};

				return _.defaults( defaults, this.constructor.__super__.defaults() );
			},

			initialize: function( attributes ) {
				if ( typeof attributes === 'object' && attributes.choices ) {
					var choices = [];

					_.each( attributes.choices, function( choice ) {
						var choiceModel = new wp.ccf.models.FieldChoice( choice );
						choiceModel.decode();

						choices.push( choiceModel );
					});

					this.set( 'choices', new wp.ccf.collections.FieldChoices( choices ) );
				}
			}
		}
	);

	wp.ccf.models.Fields.radio = wp.ccf.models.Fields.radio || wp.ccf.models.ChoiceableField.extend(
		{
			defaults: function() {
				var defaults = {
					type: 'radio'
				};

				return _.defaults( defaults, this.constructor.__super__.defaults() );
			},

			initialize: function() {
				return this.constructor.__super__.initialize.apply( this, arguments );
			}
		}
	);

	wp.ccf.models.Fields.checkboxes = wp.ccf.models.Fields.checkboxes || wp.ccf.models.ChoiceableField.extend(
		{
			defaults: function() {
				var defaults = {
					type: 'checkboxes'
				};

				return _.defaults( defaults, this.constructor.__super__.defaults() );
			},

			initialize: function() {
				return this.constructor.__super__.initialize.apply( this, arguments );
			}
		}
	);

	wp.ccf.models.Fields.dropdown = wp.ccf.models.Fields.dropdown || wp.ccf.models.ChoiceableField.extend(
		{
			defaults: function() {
				var defaults = {
					type: 'dropdown'
				};

				return _.defaults( defaults, this.constructor.__super__.defaults() );
			},

			initialize: function() {
				return this.constructor.__super__.initialize.apply( this, arguments );
			}
		}
	);
})( jQuery, Backbone, _, ccfSettings, WP_API_Settings );
( function( $, Backbone, _, ccfSettings ) {
	'use strict';

	wp.ccf.collections = wp.ccf.collections || {};

	wp.ccf.collections.Forms = wp.ccf.collections.Forms || wp.api.collections.Posts.extend(
		{
			model: wp.ccf.models.Form,

			url: WP_API_Settings.root + '/ccf/forms',

			formsFetching: {},

			initialize: function() {
				this.constructor.__super__.initialize();
				this.formsFetching = {};
			},

			remove: function( model, options ) {
				options = options || {};

				var result = this.constructor.__super__.remove.call( this, model, options );

				if ( options.destroy ) {

					if ( model instanceof Array ) {
						_.each( model, function( model ) {
							model.destroy();
						});
					} else {
						model.destroy();
					}
				}

				return result;
			}
		}
	);

	wp.ccf.collections.Fields = wp.ccf.collections.Fields || wp.api.collections.Posts.extend(
		{
			model: wp.ccf.models.Field,

			url: function() {
				return WP_API_Settings.root + '/ccf/forms/' + this.formId + '/fields';
			},

			initialize: function( models, options ) {
				if ( options && options.formId ) {
					this.formId = options.formId;
				}
			}
		}
	);

	wp.ccf.collections.Submissions = wp.ccf.collections.Submissions || wp.api.collections.Posts.extend(
		{
			model: wp.ccf.models.Submission,

			url: function() {
				return WP_API_Settings.root + '/ccf/forms/' + this.formId + '/submissions';
			},

			initialize: function( models, options ) {
				this.constructor.__super__.initialize.apply( this, arguments );

				if ( options && options.formId ) {
					this.formId = options.formId;
				}
			}
		}
	);

	wp.ccf.collections.FieldChoices = wp.ccf.collections.FieldChoices || Backbone.Collection.extend(
		{
			model: wp.ccf.models.FieldChoice
		}
	);

})( jQuery, Backbone, _, ccfSettings );
( function( $, Backbone, _, ccfSettings ) {
	'use strict';

	wp.ccf.views = wp.ccf.views || {};
	wp.ccf.views.Fields = wp.ccf.views.Fields || {};

	wp.ccf.views.FieldChoice = Backbone.View.extend(
		{
			template: _.template( document.getElementById( 'ccf-field-choice-template' ).innerHTML ),
			className: 'choice',

			events: {
				'click .add': 'triggerAdd',
				'click .delete': 'triggerDelete',
				'blur input': 'saveChoice',
				'saveChoice': 'saveChoice',
				'sorted': 'triggerUpdateSort'
			},

			initialize: function( options ) {
				this.field = options.field;
			},

			destroy: function() {
				wp.ccf.dispatcher.off( 'mainViewChange', this.saveChoice );
				this.unbind();
			},

			triggerUpdateSort: function( event, index ) {
				this.field.get( 'choices' ).remove( this.model, { silent: true } );

				this.field.get( 'choices' ).add( this.model, { at: index, silent: true } );
			},

			saveChoice: function() {
				// @todo: fix this ie8 hack
				if ( this.el.innerHTML === '' ) {
					return;
				}

				var label = this.el.querySelectorAll( '.choice-label' )[0].value;
				var value = this.el.querySelectorAll( '.choice-value' )[0].value;

				this.model.set( 'label', label );
				this.model.set( 'value', value );

				var selectedElement = this.el.querySelectorAll( '.choice-selected' )[0];
				var selected = ( selectedElement.checked ) ? true : false;

				this.model.set( 'selected', selected );

				return this;

			},

			render: function() {
				var context = {};
				if ( this.model ) {
					context.choice = this.model.toJSON();
				}

				this.el.innerHTML = this.template( context );

				wp.ccf.dispatcher.on( 'mainViewChange', this.saveChoice, this );

				return this;
			},

			triggerAdd: function() {
				this.field.get( 'choices' ).add( new wp.ccf.models.FieldChoice() );
			},

			triggerDelete: function() {
				var choices = this.field.get( 'choices' );
				if ( choices.length > 1 ) {
					choices.remove( this.model );
					this.destroy();
					this.remove();
				} else {
					var inputs = this.el.querySelectorAll( '.choice-label, .choice-value' );
					var selected = this.el.querySelectorAll( '.choice-selected' );

					for ( var i = 0; i < inputs.length; i++ ) {
						inputs[i].value = '';
					}

					selected[0].checked = false;
				}
			}
		}
	);

	wp.ccf.views.FieldBase = wp.ccf.views.FieldBase || Backbone.View.extend(
		{
			events: {
				'blur input': 'saveField',
				'blur input.field-slug': 'checkSlug',
				'blur textarea': 'saveField',
				'change select': 'saveField',
				'change input[type="checkbox"]': 'saveField'
			},

			checkSlug: function() {
				var slugSelection = this.el.querySelectorAll( '.field-slug' );

				if ( slugSelection.length > 0 ) {
					var slug = slugSelection[0];

					if ( slug.value && ! slug.value.match( /^[a-zA-Z0-9\-_]+$/ ) ) {
						slug.parentNode.className = slug.parentNode.className.replace( / field-error/i, '' ) + ' field-error';
					} else {
						slug.parentNode.className = slug.parentNode.className.replace( / field-error/i, '' );
					}
				}

			},

			destroy: function() {
				this.unbind();
			},

			render: function() {
				this.el.innerHTML = this.template( { field: this.model.toJSON() } );

				this.checkSlug();

				return this;
			}
		}
	);

	wp.ccf.views.Fields['single-line-text'] = wp.ccf.views.Fields['single-line-text'] || wp.ccf.views.FieldBase.extend(
		{
			template: _.template( document.getElementById( 'ccf-single-line-text-template' ).innerHTML ),

			initialize: function() {

			},

			saveField: function() {

				this.model.set( 'slug', this.el.querySelectorAll( '.field-slug' )[0].value );
				this.model.set( 'label', this.el.querySelectorAll( '.field-label' )[0].value );
				this.model.set( 'value', this.el.querySelectorAll( '.field-value' )[0].value );
				this.model.set( 'placeholder', this.el.querySelectorAll( '.field-placeholder' )[0].value );
				this.model.set( 'className', this.el.querySelectorAll( '.field-class-name' )[0].value );
				this.model.set( 'required', ( this.el.querySelectorAll( '.field-required' )[0].value == 1 ) ? true : false  );

				return this;
			}
		}
	);

	wp.ccf.views.Fields.recaptcha = wp.ccf.views.Fields.recaptcha || wp.ccf.views.FieldBase.extend(
		{
			template: _.template( document.getElementById( 'ccf-recaptcha-template' ).innerHTML ),

			initialize: function() {

			},

			saveField: function() {
				this.model.set( 'label', this.el.querySelectorAll( '.field-label' )[0].value );
				this.model.set( 'siteKey', this.el.querySelectorAll( '.field-site-key' )[0].value );
				this.model.set( 'secretKey', this.el.querySelectorAll( '.field-secret-key' )[0].value );
				this.model.set( 'className', this.el.querySelectorAll( '.field-class-name' )[0].value );

				return this;
			}
		}
	);

	wp.ccf.views.Fields['section-header'] = wp.ccf.views.Fields['section-header'] || wp.ccf.views.FieldBase.extend(
		{
			template: _.template( document.getElementById( 'ccf-section-header-template' ).innerHTML ),

			initialize: function() {

			},

			saveField: function() {
				this.model.set( 'heading', this.el.querySelectorAll( '.field-heading' )[0].value );
				this.model.set( 'subheading', this.el.querySelectorAll( '.field-subheading' )[0].value );
				this.model.set( 'className', this.el.querySelectorAll( '.field-class-name' )[0].value );

				return this;
			}
		}
	);

	wp.ccf.views.Fields.html = wp.ccf.views.Fields.html || wp.ccf.views.FieldBase.extend(
		{
			template: _.template( document.getElementById( 'ccf-html-template' ).innerHTML ),

			initialize: function() {

			},

			saveField: function() {
				this.model.set( 'html', this.el.querySelectorAll( '.field-html' )[0].value );
				this.model.set( 'className', this.el.querySelectorAll( '.field-class-name' )[0].value );

				return this;
			}
		}
	);

	wp.ccf.views.Fields['paragraph-text'] = wp.ccf.views.Fields['paragraph-text'] || wp.ccf.views.FieldBase.extend(
		{
			template: _.template( document.getElementById( 'ccf-paragraph-text-template' ).innerHTML ),

			saveField: function() {

				this.model.set( 'slug', this.el.querySelectorAll( '.field-slug' )[0].value );
				this.model.set( 'label', this.el.querySelectorAll( '.field-label' )[0].value );
				this.model.set( 'value', this.el.querySelectorAll( '.field-value' )[0].value );
				this.model.set( 'placeholder', this.el.querySelectorAll( '.field-placeholder' )[0].value );
				this.model.set( 'className', this.el.querySelectorAll( '.field-class-name' )[0].value );
				this.model.set( 'required', ( this.el.querySelectorAll( '.field-required' )[0].value == 1 ) ? true : false  );

				return this;
			}
		}
	);

	wp.ccf.views.Fields.hidden = wp.ccf.views.Fields.hidden || wp.ccf.views.FieldBase.extend(
		{
			template: _.template( document.getElementById( 'ccf-hidden-template' ).innerHTML ),

			saveField: function() {

				this.model.set( 'slug', this.el.querySelectorAll( '.field-slug' )[0].value );
				this.model.set( 'value', this.el.querySelectorAll( '.field-value' )[0].value );
				this.model.set( 'className', this.el.querySelectorAll( '.field-class-name' )[0].value );

				return this;
			}
		}
	);

	wp.ccf.views.Fields.date = wp.ccf.views.Fields.date || wp.ccf.views.FieldBase.extend(
		{
			template: _.template( document.getElementById( 'ccf-date-template' ).innerHTML ),

			saveField: function() {

				this.model.set( 'slug', this.el.querySelectorAll( '.field-slug' )[0].value );
				this.model.set( 'label', this.el.querySelectorAll( '.field-label' )[0].value );

				var value = this.el.querySelectorAll( '.field-value' );
				if ( value.length > 0 ) {
					this.model.set( 'value', value[0].value );
				}

				this.model.set( 'className', this.el.querySelectorAll( '.field-class-name' )[0].value );
				this.model.set( 'showDate', ( this.el.querySelectorAll( '.field-show-date' )[0].checked ) ? true : false );

				var oldShowTime = this.model.get( 'showTime' );
				var showTime = ( this.el.querySelectorAll( '.field-show-time' )[0].checked ) ? true : false;

				this.model.set( 'showTime', showTime );
				this.model.set( 'required', ( this.el.querySelectorAll( '.field-required' )[0].value == 1 ) ? true : false  );

				if ( showTime != oldShowTime ) {
					this.render();
				}

				return this;
			}
		}
	);

	wp.ccf.views.Fields.name = wp.ccf.views.Fields.name || wp.ccf.views.FieldBase.extend(
		{
			template: _.template( document.getElementById( 'ccf-name-template' ).innerHTML ),

			saveField: function() {

				this.model.set( 'slug', this.el.querySelectorAll( '.field-slug' )[0].value );
				this.model.set( 'label', this.el.querySelectorAll( '.field-label' )[0].value );
				this.model.set( 'className', this.el.querySelectorAll( '.field-class-name' )[0].value );
				this.model.set( 'required', ( this.el.querySelectorAll( '.field-required' )[0].value == 1 ) ? true : false  );

				return this;
			}
		}
	);

	wp.ccf.views.Fields.website = wp.ccf.views.Fields.website || wp.ccf.views.FieldBase.extend(
		{
			template: _.template( document.getElementById( 'ccf-website-template' ).innerHTML ),

			saveField: function() {

				this.model.set( 'slug', this.el.querySelectorAll( '.field-slug' )[0].value );
				this.model.set( 'label', this.el.querySelectorAll( '.field-label' )[0].value );
				this.model.set( 'value', this.el.querySelectorAll( '.field-value' )[0].value );
				this.model.set( 'placeholder', this.el.querySelectorAll( '.field-placeholder' )[0].value );
				this.model.set( 'className', this.el.querySelectorAll( '.field-class-name' )[0].value );
				this.model.set( 'required', ( this.el.querySelectorAll( '.field-required' )[0].value == 1 ) ? true : false  );

				return this;
			}
		}
	);

	wp.ccf.views.Fields.phone = wp.ccf.views.Fields.phone || wp.ccf.views.FieldBase.extend(
		{
			template: _.template( document.getElementById( 'ccf-phone-template' ).innerHTML ),

			saveField: function() {

				this.model.set( 'slug', this.el.querySelectorAll( '.field-slug' )[0].value );
				this.model.set( 'label', this.el.querySelectorAll( '.field-label' )[0].value );
				this.model.set( 'value', this.el.querySelectorAll( '.field-value' )[0].value );
				this.model.set( 'placeholder', this.el.querySelectorAll( '.field-placeholder' )[0].value );
				this.model.set( 'phoneFormat', this.el.querySelectorAll( '.field-phone-format' )[0].value );
				this.model.set( 'className', this.el.querySelectorAll( '.field-class-name' )[0].value );
				this.model.set( 'required', ( this.el.querySelectorAll( '.field-required' )[0].value == 1 ) ? true : false  );

				return this;
			}
		}
	);

	wp.ccf.views.Fields.address = wp.ccf.views.Fields.address || wp.ccf.views.FieldBase.extend(
		{
			template: _.template( document.getElementById( 'ccf-address-template' ).innerHTML ),

			saveField: function() {

				this.model.set( 'slug', this.el.querySelectorAll( '.field-slug' )[0].value );
				this.model.set( 'label', this.el.querySelectorAll( '.field-label' )[0].value );
				this.model.set( 'addressType', this.el.querySelectorAll( '.field-address-type' )[0].value );
				this.model.set( 'className', this.el.querySelectorAll( '.field-class-name' )[0].value );
				this.model.set( 'required', ( this.el.querySelectorAll( '.field-required' )[0].value == 1 ) ? true : false  );

				return this;
			}
		}
	);

	wp.ccf.views.Fields.email = wp.ccf.views.Fields.email || wp.ccf.views.FieldBase.extend(
		{
			template: _.template( document.getElementById( 'ccf-email-template' ).innerHTML ),

			saveField: function() {

				this.model.set( 'slug', this.el.querySelectorAll( '.field-slug' )[0].value );
				this.model.set( 'label', this.el.querySelectorAll( '.field-label' )[0].value );

				var value = this.el.querySelectorAll( '.field-value' );
				if ( value.length ) {
					this.model.set( 'value', value[0].value );
				}

				var placeholder = this.el.querySelectorAll( '.field-placeholder' );
				if ( placeholder.length ) {
					this.model.set( 'placeholder', placeholder[0].value );
				}

				this.model.set( 'className', this.el.querySelectorAll( '.field-class-name' )[0].value );
				this.model.set( 'required', ( this.el.querySelectorAll( '.field-required' )[0].value == 1 ) ? true : false  );

				var emailConfirmation = ( this.el.querySelectorAll( '.field-email-confirmation' )[0].value == 1 ) ? true : false;
				var oldEmailConfirmation = this.model.get( 'emailConfirmation' );

				this.model.set( 'emailConfirmation', emailConfirmation );

				if ( oldEmailConfirmation != emailConfirmation ) {
					this.render();
				}

				return this;
			}
		}
	);

	wp.ccf.views.ChoiceableField = wp.ccf.views.ChoiceableField || wp.ccf.views.FieldBase.extend(
		{
			template: _.template( document.getElementById( 'ccf-dropdown-template' ).innerHTML ),

			initialize: function() {
				var choices = this.model.get( 'choices' );
				this.listenTo( choices, 'add', this.addChoice );
			},

			addChoice: function( model ) {
				var view = new wp.ccf.views.FieldChoice( { model: model, field: this.model } ).render();
				var choices = this.el.querySelectorAll( '.repeatable-choices' )[0];

				choices.appendChild( view.el );
			},

			saveField: function() {

				this.model.set( 'slug', this.el.querySelectorAll( '.field-slug' )[0].value );
				this.model.set( 'label', this.el.querySelectorAll( '.field-label' )[0].value );
				this.model.set( 'className', this.el.querySelectorAll( '.field-class-name' )[0].value );
				this.model.set( 'required', ( this.el.querySelectorAll( '.field-required' )[0].value == 1 ) ? true : false  );


				var choices = this.el.querySelectorAll( '.repeatable-choices' )[0].querySelectorAll( '.choice' );

				_.each( choices, function( choice ) {
					$( choice ).trigger( 'saveChoice' );
				});

				return this;

			},

			render: function() {
				var SELF = this;

				SELF.el.innerHTML = SELF.template( { field: SELF.model.toJSON() } );

				var choicesCollection = SELF.model.get( 'choices' );

				var choices = this.el.querySelectorAll( '.repeatable-choices' )[0];

				if ( choicesCollection.length >= 1 ) {

					choicesCollection.each( function( model ) {
						var view = new wp.ccf.views.FieldChoice( { model: model, field: SELF.model } ).render();
						choices.appendChild( view.el );
					});
				} else {
					var choice = new wp.ccf.models.FieldChoice();
					choicesCollection.add( choice );
				}

				choices = this.el.querySelectorAll( '.repeatable-choices' )[0];

				$( choices ).sortable( {
					handle: '.move',
					axis: 'y',
					stop: function( event, $ui ) {
						$ui.item.trigger( 'sorted', $ui.item.index() );
					}
				});

				return SELF;
			}
		}
	);

	wp.ccf.views.Fields.dropdown = wp.ccf.views.Fields.dropdown || wp.ccf.views.ChoiceableField.extend(
		{
			template: _.template( document.getElementById( 'ccf-dropdown-template' ).innerHTML ),
			events: function() {
				return this.constructor.__super__.events;
			}
		}
	);

	wp.ccf.views.Fields.radio = wp.ccf.views.Fields.radio || wp.ccf.views.ChoiceableField.extend(
		{
			template: _.template( document.getElementById( 'ccf-radio-template' ).innerHTML ),
			events: function() {
				return this.constructor.__super__.events;
			}
		}
	);

	wp.ccf.views.Fields.checkboxes = wp.ccf.views.Fields.checkboxes || wp.ccf.views.ChoiceableField.extend(
		{
			template: _.template( document.getElementById( 'ccf-checkboxes-template' ).innerHTML ),
			events: function() {
				return this.constructor.__super__.events;
			}
		}
	);

	wp.ccf.views.FieldSidebar = wp.ccf.views.FieldSidebar || Backbone.View.extend(
		{
			initialize: function( options ) {
				this.currentFieldView = null;
				this.form = options.form;
			},

			save: function( $promise ) {
				if ( this.currentFieldView ) {

					// @todo: fix this ie8 hack
					if ( this.currentFieldView.el.innerHTML !== '' ) {
						this.currentFieldView.saveField();
					}
				}

				if ( $promise && $promise instanceof Object ) {
					$promise.resolve();
				}
			},

			fieldRemoved: function() {
				if ( this.currentFieldView ) {
					if ( ! this.form.get( 'fields' ).get( this.currentFieldView.model ) ) {
						this.render();
					}
				}
			},

			destroy: function() {
				wp.ccf.dispatcher.off( 'saveField', this.save );
				wp.ccf.dispatcher.off( 'mainViewChange', this.save );
				this.unbind();
			},

			render: function( field ) {
				var context = {};

				if ( ! field ) {
					var template = _.template( document.getElementById( 'ccf-empty-field-template' ).innerHTML );
					this.el.innerHTML = template( context );
				} else {
					var type = field.get( 'type' );

					if ( this.currentFieldView ) {
						this.currentFieldView.saveField();

						if ( this.currentFieldView.destroy ) {
							this.currentFieldView.destroy();
						}
					}

					this.currentFieldView = new wp.ccf.views.Fields[type]( { model: field } );

					this.currentFieldView.render();

					this.el.innerHTML = '';

					this.el.appendChild( this.currentFieldView.el );

					var fields = this.form.get( 'fields' );
					this.listenTo( fields, 'remove', this.fieldRemoved );
				}

				wp.ccf.dispatcher.on( 'saveField', this.save, this );
				wp.ccf.dispatcher.on( 'mainViewChange', this.save, this );

				return this;
			}
		}
	);

	wp.ccf.views.FieldRowPlaceholder = wp.ccf.views.FieldRowPlaceholder || Backbone.View.extend(
		{
			template: _.template( document.getElementById( 'ccf-field-row-template').innerHTML ),
			tagName: 'div',
			className: 'field',

			initialize: function( options ) {
				this.type = options.type;
			},

			render: function() {
				this.el.innerHTML = this.template( { label: ccfSettings.allLabels[this.type] } );

				this.el.setAttribute( 'data-field-type', this.type );
				this.el.className += ' ' + this.type;

				return this;
			}
		}
	);

	wp.ccf.views.FieldRow = wp.ccf.views.FieldRow || Backbone.View.extend(
		{
			template: _.template( document.getElementById( 'ccf-field-row-template').innerHTML ),
			tagName: 'div',
			className: 'field',

			events: {
				'click .delete': 'triggerDelete',
				'click h4': 'triggerEdit',
				'sorted': 'triggerUpdateSort'
			},

			initialize: function( options ) {
				_.bindAll( this, 'triggerDelete' );
				this.form = options.form;

				this.listenTo( this.model, 'change', this.handleChange, this );
				this.listenTo( this.model, 'requirementsNotMet', this.requirementsNotMet, this );
				this.listenTo( this.model, 'requirementsMet', this.requirementsMet, this );
				this.listenTo( this.model, 'duplicateSlug', this.duplicateSlug, this );

				if ( this.model.attributes.choices ) {
					this.listenTo( this.model.attributes.choices, 'change', this.handleChange, this );
				}
			},

			duplicateSlug: function() {
				this.requirementsMet();
				this.el.className += ' field-duplicate-slug';
			},

			requirementsNotMet: function() {
				this.requirementsMet();
				this.el.className += ' field-incomplete';
			},

			requirementsMet: function() {
				this.el.className = this.el.className.replace( /(field-incomplete|field-duplicate-slug)/i, '' );
			},

			triggerUpdateSort: function( event, index ) {
				this.form.get( 'fields' ).remove( this.model );

				this.form.get( 'fields' ).add( this.model, { at: index } );
			},

			handleChange: function( event ) {
				this.render();
			},

			triggerDelete: function( event ) {
				event.stopPropagation();

				this.form.get( 'fields' ).remove( this.model );
				this.undelegateEvents();
				this.remove();
			},

			triggerEdit: function( event ) {
				var editing = this.el.parentNode.querySelectorAll( '.ccf-editing' );
				_.each( editing, function( node ) {
					node.className = node.className.replace( /ccf-editing/i, '' );
				});

				this.el.className = this.el.className.replace( /ccf-editing/i, '' ) + ' ccf-editing';
				wp.ccf.dispatcher.trigger( 'openEditField', this.model );
			},

			render: function( instantiate ) {
				this.el.innerHTML = this.template( { label: ccfSettings.allLabels[this.model.get( 'type' )] } );

				this.el.setAttribute( 'data-field-type', this.model.get( 'type' ) );

				var regex = new RegExp( ' ' + this.model.get( 'type' ), 'i' );

				this.el.className = this.el.className.replace( regex, '' ) + ' ' + this.model.get( 'type' );

				if ( instantiate ) {
					this.el.className = this.el.className.replace( / instantiated/i, '' ) + ' instantiated';
				}

				var previewTemplate = document.getElementById( 'ccf-' + this.model.get( 'type' ) + '-preview-template' );

				if ( previewTemplate ) {
					var preview = this.el.querySelectorAll( '.preview' )[0];
					preview.style.display = 'block';
					preview.innerHTML = _.template( previewTemplate.innerHTML )( { field: this.model.toJSON() } );
				}

				return this;
			}
		}
	);

	wp.ccf.views.FormSettings = wp.ccf.views.FormSettings || Backbone.View.extend(
		{
			template: _.template( document.getElementById( 'ccf-form-settings-template' ).innerHTML ),

			events: {
				'blur input': 'save',
				'change select': 'save',
				'change select.form-completion-action-type': 'toggleCompletionFields',
				'change select.form-send-email-notifications': 'toggleNotificationFields'
			},

			initialize: function( options ) {
				this.model = options.form;
			},

			destroy: function() {
				wp.ccf.dispatcher.off( 'saveFormSettings', this.save );
				wp.ccf.dispatcher.off( 'mainViewChange', this.save );
			},

			toggleCompletionFields: function() {
				var completionActionType = this.el.querySelectorAll( '.form-completion-action-type' )[0].value;

				var completionMessage = this.el.querySelectorAll( '.completion-message' )[0];
				var completionRedirect = this.el.querySelectorAll( '.completion-redirect-url' )[0];

				if ( 'text' === completionActionType ) {
					completionMessage.style.display = 'block';
					completionRedirect.style.display = 'none';
				} else {
					completionMessage.style.display = 'none';
					completionRedirect.style.display = 'block';
				}
			},

			toggleNotificationFields: function() {
				var sendEmailNotifications = this.el.querySelectorAll( '.form-send-email-notifications' )[0].value;

				var emailNotificationAddresses = this.el.querySelectorAll( '.email-notification-addresses' )[0];

				if ( parseInt( sendEmailNotifications ) ) {
					emailNotificationAddresses.style.display = 'block';
				} else {
					emailNotificationAddresses.style.display = 'none';
				}
			},

			save: function( $promise ) {
				var SELF = this;

				if ( this.el.innerHTML === '' ) {
					// @todo: for some reason this is needed for IE8
					return;
				}

				var title = this.el.querySelectorAll( '.form-title' )[0].value;
				this.model.set( 'title', title );

				var description = this.el.querySelectorAll( '.form-description' )[0].value;
				this.model.set( 'description', description );

				var buttonText = this.el.querySelectorAll( '.form-button-text' )[0].value;
				this.model.set( 'buttonText', buttonText );

				var completionMessage = this.el.querySelectorAll( '.form-completion-message' )[0].value;
				this.model.set( 'completionMessage', completionMessage );

				var completionRedirectUrl = this.el.querySelectorAll( '.form-completion-redirect-url' )[0].value;
				this.model.set( 'completionRedirectUrl', completionRedirectUrl );

				var completionActionType = this.el.querySelectorAll( '.form-completion-action-type' )[0].value;
				this.model.set( 'completionActionType', completionActionType );

				var sendEmailNotifications = this.el.querySelectorAll( '.form-send-email-notifications' )[0].value;
				this.model.set( 'sendEmailNotifications', ( parseInt( sendEmailNotifications ) ) ? true : false );

				var emailNotificationAddresses = this.el.querySelectorAll( '.form-email-notification-addresses' )[0].value;
				this.model.set( 'emailNotificationAddresses', emailNotificationAddresses );

				if ( typeof $promise !== 'undefined' && typeof $promise.promise !== 'undefined' ) {
					$promise.resolve();
				}
			},

			render: function() {
				var context = {
					form: this.model.toJSON()
				};

				this.el.innerHTML = this.template( context );

				this.toggleCompletionFields();

				this.toggleNotificationFields();

				wp.ccf.dispatcher.on( 'saveFormSettings', this.save, this );
				wp.ccf.dispatcher.on( 'mainViewChange', this.save, this );

				return this;
			}
		}
	);

	wp.ccf.views.FormPane = wp.ccf.views.FormPane || Backbone.View.extend( _.defaults(
		{
			template: _.template( document.getElementById( 'ccf-form-pane-template' ).innerHTML ),
			subViews: {
				'field-sidebar': wp.ccf.views.FieldSidebar,
				'form-settings': wp.ccf.views.FormSettings
			},

			events: {
				'click .save-button': 'sync',
				'click h2': 'accordionClick',
				'click .insert-form-button': 'insertForm'
			},

			initialize: function() {
				wp.ccf.dispatcher.on( 'openEditField', this.openEditField, this );
			},

			insertForm: function( event ) {
				wp.ccf.utils.insertFormShortcode( this.model );

				wp.ccf.toggle();
			},

			accordionClick: function( event ) {
				var parentContainer = $( event.currentTarget ).parents( '.accordion-container' )[0];

				var sections = parentContainer.querySelectorAll( '.accordion-section' );

				if ( event.currentTarget.parentNode.className.match( /expanded/i ) ) {
					event.currentTarget.parentNode.className = event.currentTarget.parentNode.className.replace( /expanded/i, '' );
				} else {
					event.currentTarget.parentNode.className += ' expanded';
				}

				_.each( sections, function( section, index ) {
					if ( section != event.currentTarget.parentNode && section.className.match( /expanded/i ) ) {
						section.className = section.className.replace( /expanded/i, '' );
					}
				});
			},

			openEditField: function( field ) {
				this.renderedSubViews['field-sidebar'].render( field ).el.style.display = 'block';
			},

			disable: function() {
				this.el.querySelectorAll( '.save-button' )[0].setAttribute( 'disabled', 'disabled' );
				this.el.querySelectorAll( '.disabled-overlay' )[0].style.display = 'block';
			},

			enable: function() {
				this.el.querySelectorAll( '.save-button' )[0].removeAttribute( 'disabled' );
				this.el.querySelectorAll( '.disabled-overlay' )[0].style.display = 'none';
			},

			sync: function() {
				var SELF = this;

				var $spinner = $( this.el.querySelectorAll( '.spinner' )[0] );
				$spinner.fadeIn();
				SELF.disable();

				var $settings = $.Deferred();
				var $field = $.Deferred();

				wp.ccf.dispatcher.trigger( 'saveFormSettings', $settings );
				wp.ccf.dispatcher.trigger( 'saveField', $field );

				$.when( $settings, $field ).then( function() {
					var fields = SELF.model.get( 'fields' );
					var allReqsMet = true;
					var slugs = {};

					fields.each( function( field ) {
						var slug = field.get( 'slug' );
						if ( ! field.hasRequiredAttributes() ) {
							allReqsMet = false;

							field.trigger( 'requirementsNotMet' );

						} else if ( slug && ! slug.match( /^[a-zA-Z0-9\-_]+$/ ) ) {
							allReqsMet = false;

							field.trigger( 'requirementsNotMet' );
						} else if ( typeof slugs[field.get( 'slug' )] !== 'undefined' ) {
							allReqsMet = false;

							field.trigger( 'duplicateSlug' );
							slugs[field.get( 'slug' )].trigger( 'duplicateSlug' );
						} else {
							field.trigger( 'requirementsMet' );
						}

						if ( field.get( 'slug' ) ) {
							slugs[field.get( 'slug' )] = field;
						}
					});

					if ( allReqsMet ) {

						SELF.model.save( {}, { context: 'edit' } ).done( function( response ) {
							if (ccfSettings.single && ! ccfSettings.postId ) {
								window.location = ccfSettings.adminUrl + 'post.php?post=' + SELF.model.get( 'ID' ) + '&action=edit#ccf-form/' + SELF.model.get( 'ID' );
							}
						}).complete( function( response ) {
							$spinner.fadeOut();
							SELF.enable();

							wp.ccf.dispatcher.trigger( 'saveFormComplete', SELF.model );
						});
					} else {
						SELF.enable();
						$spinner.fadeOut();
					}
				});
			},

			enableDisableInsert: function() {
				var insertButton = this.el.querySelectorAll( '.insert-form-button' )[0];
				if ( this.model.get( 'ID' ) ) {
					insertButton.removeAttribute( 'disabled' );
				} else {
					insertButton.setAttribute( 'disabled', 'disabled' );
				}
			},

			render: function( form ) {
				var SELF = this;

				if ( form ) {
					SELF.model = form;
				} else {
					SELF.model = new wp.ccf.models.Form();
				}

				this.listenTo( SELF.model, 'change', this.enableDisableInsert, this );

				var context = {
					labels: ccfSettings.fieldLabels,
					form: SELF.model.toJSON()
				};

				window.form = SELF.model;

				SELF.el.innerHTML = this.template( context );

				var fields = SELF.el.querySelectorAll( '.fields' )[0];

				_.each( ccfSettings.fieldLabels, function( label, type ) {
					fields.appendChild( new wp.ccf.views.FieldRowPlaceholder( { type: type } ).render().el );
				});

				var structureFields = SELF.el.querySelectorAll( '.structure-fields' )[0];

				_.each( ccfSettings.structureFieldLabels, function( label, type ) {
					structureFields.appendChild( new wp.ccf.views.FieldRowPlaceholder( { type: type } ).render().el );
				});

				var specialFields = SELF.el.querySelectorAll( '.special-fields' )[0];

				_.each( ccfSettings.specialFieldLabels, function( label, type ) {
					specialFields.appendChild( new wp.ccf.views.FieldRowPlaceholder( { type: type } ).render().el );
				});

				$( SELF.el.querySelectorAll( '.left-sidebar' )[0].querySelectorAll( '.field' ) ).draggable( {
					cursor: 'move',
					zIndex: 160001,
					//opacity: 0.75,
					scroll: false,
					containment: '.ccf-form-pane',
					appendTo: '.ccf-main-modal',
					snap: true,
					connectToSortable: '.form-content',
					helper: function( event ) {
						var $field = $( event.currentTarget );
						var $helper = $( '<div class="field" data-field-type="' + $field.attr( 'data-field-type' ) + '"><h4>' + $field.find( '.label' ).html() + '</h4></div>' );
						return $helper.css( { 'width': $field.width(), 'height': $field.height() } );
					}

				});

				var fieldModels = SELF.model.get( 'fields' );
				var formContent = SELF.el.querySelectorAll( '.form-content' )[0];

				if ( fieldModels.length >= 1 ) {
					formContent.innerHTML = '';

					fieldModels.each( function( field ) {
						var row = new wp.ccf.views.FieldRow( { model: field, form: SELF.model } ).render( true ).el;
						formContent.appendChild( row );
					});
				}

				$( formContent ).sortable( {
					axis: 'y',
					handle: 'h4',
					stop: function( event, $ui ) {
						if ( ! $ui.item.hasClass( 'instantiated' ) ) {
							var type = $ui.item.attr( 'data-field-type' );
							var defaults = {};

							if ( typeof wp.ccf.models.Fields[type].prototype.defaults().slug !== 'undefined' ) {
								var ord = SELF.model.get( 'fields').where( { 'type': type } ).length + 1;
								defaults.slug = type + '-' + ord;
							}

							var field = new wp.ccf.models.Fields[type]( defaults );
							var fields = SELF.model.get( 'fields' );

							fields.add( field );

							new wp.ccf.views.FieldRow( { model: field, el: $ui.item, form: SELF.model } ).render( true );
							$ui.item.attr( 'style', '' );
						}

						$ui.item.trigger( 'sorted', $ui.item.index() );
					}

				});

				SELF.initRenderSubViews( true, true, { form: SELF.model } );

				SELF.enableDisableInsert();

				return SELF;
			}
		}, wp.ccf.mixins.subViewable )
	);

	wp.ccf.views.ExistingFormTableRow = wp.ccf.views.ExistingFormTableRow || Backbone.View.extend(
		{
			tagName: 'tr',
			template: _.template( document.getElementById( 'ccf-existing-form-table-row-template').innerHTML ),
			events: {
				'click .edit': 'triggerMainViewChange',
				'click .delete': 'triggerDelete',
				'click .insert-form-button': 'insertForm'
			},

			initialize: function( options) {
				this.parent = options.parent;
			},

			insertForm: function( event ) {
				wp.ccf.utils.insertFormShortcode( this.model );

				wp.ccf.toggle();
			},

			triggerMainViewChange: function() {
				wp.ccf.switchToForm( this.model );
			},

			triggerDelete: function() {
				var SELF = this,
					currentPage = SELF.parent.collection.state.currentPage,
					page;

				SELF.model.destroy().done( function() {
					page = currentPage;

					if ( page === SELF.parent.collection.state.totalPages && page - 1  === ( SELF.parent.collection.state.totalObjects - 1 ) / ccfSettings.postsPerPage ) {
						page--;
					}

					SELF.parent.showPage( page ).done( function() {
						SELF.parent.renderPagination();
					});
				});
			},

			render: function() {
				this.$el.html( this.template( { form: this.model.toJSON(), utils: { getPrettyPostDate: wp.ccf.utils.getPrettyPostDate } } ) );
				return this;
			}
		}
	);

	wp.ccf.views.EmptyFormTableRow = wp.ccf.views.EmptyFormTableRow || Backbone.View.extend(
		{
			tagName: 'tr',
			template: _.template( document.getElementById( 'ccf-empty-form-table-row-template').innerHTML ),

			render: function() {
				this.$el.html( this.template() );
				return this;
			}
		}
	);

	wp.ccf.views.ExistingFormTable = wp.ccf.views.ExistingFormTable || Backbone.View.extend(
		{
			template: _.template( document.getElementById( 'ccf-existing-form-table-template').innerHTML ),

			initialize: function() {
				this.parent = arguments.parent;
				this.collection = new wp.ccf.collections.Forms();

				wp.ccf.dispatcher.on( 'changeFormTablePage', this.showPage, this );

				// IMPORTANT
				wp.ccf.dispatcher.on( 'saveFormComplete', this.render, this );
			},

			showPage: function( page ) {
				var SELF = this;

				var fetch = this.collection.fetch( { data: { page: ( page ) } } );

				fetch.done( function() {
					var rowContainer = SELF.el.querySelectorAll( '.rows' )[0];
					var newRowContainer = document.createElement( 'tbody');

					newRowContainer.className = 'rows';

					if ( SELF.collection.length >= 1 ) {
						SELF.collection.each( function( model ) {
							var row = new wp.ccf.views.ExistingFormTableRow( { model: model, parent: SELF } ).render();
							newRowContainer.appendChild( row.el );
						}, SELF );
					} else {
						newRowContainer.appendChild( new wp.ccf.views.EmptyFormTableRow().render().el );
					}

					rowContainer.parentNode.replaceChild( newRowContainer, rowContainer );
				});

				return fetch;
			},

			renderPagination: function() {
				var container = this.el.querySelectorAll( '.ccf-pagination' )[0];

				container.innerHTML = '';
				if ( this.collection.state.totalPages > 1 ) {
					container.appendChild( new wp.ccf.views.Pagination( { parent: this } ).render( this.collection.state.totalPages, this.collection.state.currentPage ).el );
				}
			},

			render: function( skipRefreshPage ) {
				var SELF = this;

				this.el.innerHTML = this.template();

				var pagination = this.el.querySelectorAll( '.ccf-pagination' )[0];

				this.showPage( 1 ).done( function() {
					SELF.renderPagination();
				});

				return this;
			}
		}
	);

	wp.ccf.views.ExistingFormPane = wp.ccf.views.ExistingFormPane || Backbone.View.extend( _.defaults(
		{
			template: _.template( document.getElementById( 'ccf-existing-form-pane-template' ).innerHTML ),
			subViews: {
				'existing-form-table': wp.ccf.views.ExistingFormTable
			},

			render: function() {

				if ( this.rendered ) {
					return this;
				}

				this.rendered = true;

				this.el.innerHTML = this.template();
				this.initRenderSubViews( true );

				return this;
			}
		}, wp.ccf.mixins.subViewable )
	);

	wp.ccf.views.MainModal = wp.ccf.views.MainModal || Backbone.View.extend( _.defaults(
		{
			tagName: 'div',
			className: 'ccf-main-modal',
			template: _.template( document.getElementById( 'ccf-main-modal-template' ).innerHTML ),
			events: {
				'click .close-icon': 'hide',
				'click .main-menu a': 'menuClick'
			},

			subViews: {
				'form-pane': wp.ccf.views.FormPane
			},

			initialize: function() {
				if ( ! ccfSettings.single ) {
					this.subViews['existing-form-pane'] = wp.ccf.views.ExistingFormPane;
				}

				wp.ccf.dispatcher.on( 'mainViewChange', this.toggleView, this );
			},

			toggleView: function( view ) {
				this.showView( view, wp.ccf.currentForm );

				var menuView = view;

				if ( 'form-pane' === view && wp.ccf.currentForm ) {
					menuView = 'existing-form-pane';
				}

				var items = this.el.querySelectorAll( '.menu-item' );

				_.each( items, function( item ) {
					var itemView = item.getAttribute( 'data-view' );

					if ( itemView === menuView ) {
						item.className = item.className.replace( 'selected', '' ) + ' selected';
					} else {
						item.className = item.className.replace( 'selected', '' );
					}
				});
			},

			menuClick: function( event ) {
				var view = event.target.getAttribute( 'data-view' );

				if ( 'form-pane' === view ) {
					wp.ccf.currentForm = null;
				}

				wp.ccf.dispatcher.trigger( 'mainViewChange', view );

				event.preventDefault();
			},

			render: function( single ) {
				single = single || false;

				this.overlay();

				this.el.innerHTML = this.template( { single: single } );
				this.initRenderSubViews();

				this.showView( 'form-pane', wp.ccf.currentForm, true );

				return this;
			},

			overlay: function() {
				if ( typeof this.overlayEl === 'undefined' ) {
					this.overlayEl = document.createElement( 'div' );
					this.overlayEl.className = 'ccf-main-modal-overlay';
					document.body.appendChild( this.overlayEl );
				}

				return this.overlayEl;
			},

			remove: function() {
				document.body.removeChild( this.overlay() );

				return this;
			},

			show: function() {
				$( this.overlay() ).show();
				this.$el.show();
			},

			hide: function() {
				$( this.overlay() ).hide();
				this.$el.hide();
			}
		}, wp.ccf.mixins.subViewable )
	);

	wp.ccf.views.SubmissionRow = wp.ccf.views.SubmissionRow || Backbone.View.extend(
		{
			tagName: 'tr',
			template: _.template( document.getElementById( 'ccf-submission-row-template' ).innerHTML ),
			events: {
				'click .view': 'view',
				'click .delete': 'delete'
			},

			initialize: function( options ) {
				this.parent = options.parent;
			},

			'delete': function() {
				var SELF = this,
					currentPage = SELF.parent.collection.state.currentPage,
					page;

				SELF.model.destroy().done( function() {
					page = currentPage;

					if ( page === SELF.parent.collection.state.totalPages && page - 1  === ( SELF.parent.collection.state.totalObjects - 1 ) / ccfSettings.postsPerPage ) {
						page--;
					}

					SELF.parent.showPage( page ).done( function() {
						SELF.parent.renderPagination();
					});
				});
			},

			view: function( event ) {
				var id = event.currentTarget.getAttribute( 'data-submission-id'),
					date = event.currentTarget.getAttribute( 'data-submission-date');

				tb_show( ccfSettings.thickboxTitle + ' - ' + wp.ccf.utils.getPrettyPostDate( date ), '#TB_inline?height=500&amp;width=700&amp;inlineId=ccf-submission-content-' + parseInt( id ), null );
			},

			render: function() {
				this.$el.html( this.template( {
					submission: this.model.toJSON(),
					currentColumns: this.parent.columns,
					columns: wp.ccf.currentForm.getFieldSlugs( true ),
					utils: {
						getPrettyPostDate: wp.ccf.utils.getPrettyPostDate,
						wordChop: wp.ccf.utils.wordChop,
						isFieldDate: wp.ccf.utils.isFieldDate,
						isFieldName: wp.ccf.utils.isFieldName,
						isFieldAddress: wp.ccf.utils.isFieldAddress,
						getPrettyFieldDate: wp.ccf.utils.getPrettyFieldDate,
						getPrettyFieldAddress: wp.ccf.utils.getPrettyFieldAddress,
						getPrettyFieldName: wp.ccf.utils.getPrettyFieldName
					}
				} ) );

				return this;
			}
		}
	);


	wp.ccf.views.SubmissionsTable = wp.ccf.views.SubmissionsTable || Backbone.View.extend(
		{
			template: _.template( document.getElementById( 'ccf-submission-table-template' ).innerHTML ),
			events: {
				'click .prev:not(.disabled)': 'previousPage',
				'click .next:not(.disabled)': 'nextPage',
				'click .first:not(.disabled)': 'firstPage',
				'click .last:not(.disabled)': 'lastPage'
			},

			initialize: function() {
				this.collection = new wp.ccf.collections.Submissions( {}, { formId: ccfSettings.postId } );
				wp.ccf.dispatcher.on( 'submissionTableRebuild', this.render, this );
			},

			showPage: function( page ) {
				var SELF = this;

				var fetch = this.collection.fetch( { data: { page: ( page ) } } );

				fetch.done( function() {
					var rowContainer = SELF.el.querySelectorAll( '.submission-rows' )[0];
					var newRowContainer = document.createElement( 'tbody');

					newRowContainer.className = 'submission-rows';

					if ( SELF.collection.length >= 1 ) {
						SELF.collection.each( function( submission ) {
							var row = new wp.ccf.views.SubmissionRow( { model: submission, parent: SELF } ).render();
							newRowContainer.appendChild( row.el );
						}, SELF );
					} else {
						newRowContainer.appendChild( new wp.ccf.views.EmptySubmissionTableRow( { parent: SELF } ).render( wp.ccf.currentForm.getFieldSlugs( true ).concat( 'date' ) ).el );
					}

					rowContainer.parentNode.replaceChild( newRowContainer, rowContainer );
				});

				return fetch;
			},

			renderPagination: function() {
				var container = this.el.querySelectorAll( '.ccf-pagination' )[0];

				container.innerHTML = '';
				if ( this.collection.state.totalPages > 1 ) {
					container.appendChild( new wp.ccf.views.Pagination( { parent: this } ).render( this.collection.state.totalPages, this.collection.state.currentPage ).el );
				}
			},

			render: function( columns ) {
				var SELF = this;

				if ( ! columns ) {
					SELF.columns = wp.ccf.currentForm.getFieldSlugs( true ).slice( 0, 4 ).concat( 'date' );
				} else {
					SELF.columns = columns;
				}

				if ( SELF.columns.length < 1 ) {
					SELF.el.innerHTML = '';
				} else {
					SELF.el.innerHTML = SELF.template( { columns: SELF.columns } );

					var pagination = SELF.el.querySelectorAll( '.ccf-pagination' )[0];

					SELF.showPage( 1 ).done( function() {
						SELF.renderPagination();
					});
				}

				return SELF;
			}
		}
	);

	wp.ccf.views.Pagination = wp.ccf.views.Pagination || Backbone.View.extend(
		{
			template: _.template( document.getElementById( 'ccf-pagination-template' ).innerHTML ),

			events: {
				'click .prev:not(.disabled)': 'previousPage',
				'click .next:not(.disabled)': 'nextPage',
				'click .first:not(.disabled)': 'firstPage',
				'click .last:not(.disabled)': 'lastPage'
			},

			initialize: function( options ) {
				this.parent = options.parent;
			},

			previousPage: function( event ) {
				var SELF = this;

				SELF.parent.showPage( SELF.parent.collection.state.currentPage - 1 ).done( function() {
					SELF.render();
				});
			},

			nextPage: function( event ) {
				var SELF = this;

				SELF.parent.showPage( SELF.parent.collection.state.currentPage + 1 ).done( function() {
					SELF.render();
				});
			},

			firstPage: function( event ) {
				var SELF = this;

				SELF.parent.showPage( 1 ).done( function() {
					SELF.render();
				});
			},

			lastPage: function( event ) {
				var SELF = this;

				SELF.parent.showPage( SELF.parent.collection.state.totalPages ).done( function() {
					SELF.render();
				});
			},

			render: function() {
				this.el.innerHTML = this.template( { totalPages: this.parent.collection.state.totalPages, currentPage: this.parent.collection.state.currentPage, totalObjects: this.parent.collection.state.totalObjects } );

				return this;
			}
		}
	);

	wp.ccf.views.EmptySubmissionTableRow = wp.ccf.views.EmptySubmissionTableRow || Backbone.View.extend(
		{
			tagName: 'tr',
			template: _.template( document.getElementById( 'ccf-no-submissions-row-template').innerHTML ),

			initialize: function( options ) {
				this.parent = options.parent;
			},

			render: function( columns ) {
				this.el.innerHTML = this.template( { columns: this.parent.columns } );
				return this;
			}
		}
	);

	wp.ccf.views.SubmissionColumnController = wp.ccf.views.SubmissionColumnController || Backbone.View.extend(
		{
			template: _.template( document.getElementById( 'ccf-submissions-controller-template').innerHTML ),

			events: {
				'click input[type=checkbox]': 'triggerTableRebuild'
			},

			render: function() {
				this.el.innerHTML = this.template( { columns: wp.ccf.currentForm.getFieldSlugs( true ).concat( 'date' ) } );
			},

			triggerTableRebuild: function( event ) {
				var columns = [];

				var columnElements = document.querySelectorAll( '.submission-column-checkbox' );

				if ( columnElements.length >= 1 ) {
					for ( var i = 0; i < columnElements.length; i++ ) {
						if ( columnElements[i].checked ) {
							columns.push( columnElements[i].value );
						}
					}
				}

				wp.ccf.dispatcher.trigger( 'submissionTableRebuild', columns );
			}
		}
	);
})( jQuery, Backbone, _, ccfSettings );
( function( $, Backbone, _, ccfSettings ) {
	'use strict';

	wp.ccf.router = wp.ccf.router || Backbone.Router.extend( {
		routes: {
			'ccf-form': 'open',
			'ccf-form/:formId': 'open'
		},

		open: function( formId ) {
			wp.ccf.show( formId );
		}
	});
})( jQuery, Backbone, _, ccfSettings );
( function( $, Backbone, _, ccfSettings ) {
	'use strict';

	/**
	 * Wrapper object for CCF form manager
	 *
	 * @returns {{}}
	 */
	wp.ccf = _.defaults( wp.ccf, {
		forms: new wp.ccf.collections.Forms(),

		currentForm: null,

		// Used for single form pages
		_currentFormDeferred: null,

		dispatcher: {},

		show: function( form ) {
			this.switchToForm( form );

			this.instance.show();
			return this.instance;
		},

		switchToForm: function( form ) {
			var SELF = this;

			if ( +form === parseInt( form ) ) {
				var formId = parseInt( form );

				form = SELF.forms.findWhere( { ID: parseInt( formId ) } );

				if ( ! form ) {
					var $deferred;

					if ( typeof SELF.forms.formsFetching[formId] !== 'undefined' ) {
						$deferred = SELF.forms.formsFetching[formId];
						form = null;
					} else {
						form = new wp.ccf.models.Form( { ID: formId } );
						$deferred = form.fetch();
						SELF.forms.formsFetching[formId] = $deferred;
					}

					$deferred.done( function() {
						if ( form ) {
							delete SELF.forms.formsFetching[formId];
							SELF.forms.add( form );
						} else {
							form = SELF.forms.findWhere( { ID: formId } );
						}

						SELF.currentForm = form;

						wp.ccf.dispatcher.trigger( 'mainViewChange', 'form-pane' );
					});

					return $deferred;
				} else {
					SELF.currentForm = form;

					wp.ccf.dispatcher.trigger( 'mainViewChange', 'form-pane' );
				}
			} else {
				SELF.currentForm = form;

				wp.ccf.dispatcher.trigger( 'mainViewChange', 'form-pane' );
			}

			return true;
		},

		hide: function() {
			this.instance.hide();
			return this.instance;
		},

		toggle: function( form ) {
			this.switchToForm( form );

			if ( this.instance.$el.is( ':visible' ) ) {
				this.instance.hide();
			} else {
				this.instance.show();
			}

			return this.instance;
		},

		createSubmissionsTable: function( container ) {
			var columns = [];

			var columnControllerContainer = document.querySelectorAll( '.ccf-submission-column-controller' );

			var main = new wp.ccf.views.SubmissionsTable( { el: container } );

			main.render();

			if ( columnControllerContainer ) {
				( new wp.ccf.views.SubmissionColumnController( { el: columnControllerContainer } ) ).render();
			}
		},

		_setupMainModal: function( single ) {
			this.instance = new wp.ccf.views.MainModal().render( single );

			document.getElementsByTagName( 'body' )[0].appendChild( this.instance.el );

			Backbone.history.start();

			return this.instance;
		},

		createManager: function() {
			var SELF = this;

			_.extend( this.dispatcher, Backbone.Events );

			new wp.ccf.router();

			var single = false;

			var managerButton = document.querySelectorAll( '.ccf-open-form-manager')[0];

			if ( ccfSettings.single ) {
				single = true;

				if ( ccfSettings.postId ) {
					var formId = parseInt( ccfSettings.postId );

					if ( typeof SELF.forms.formsFetching[formId] === 'undefined' ) {

						var form = new wp.ccf.models.Form( { ID: formId } );
						var $deferred = form.fetch();
						SELF.forms.formsFetching[formId] = $deferred;
						SELF._currentFormDeferred = $deferred;

						$deferred.done( function() {
							delete SELF.forms.formsFetching[formId];
							SELF.forms.add( form );
							SELF.currentForm = form;
						});
					} else {
						SELF._currentFormDeferred = SELF.forms.formsFetching[formId];

						SELF._currentFormDeferred.done( function() {
							SELF.currentForm = SELF.forms.findWhere( { 'ID': formId } );
						});
					}

					$.when( SELF._currentFormDeferred ).then( function() {
						SELF._setupMainModal( true );
						managerButton.style.display = 'inline-block';

						var metabox = document.getElementById( 'ccf-submissions' );
						if ( metabox ) {
							var container = metabox.querySelectorAll( '.inside' )[0];

							var settings = document.createElement( 'div' );
							settings.className = 'ccf-submission-settings';
							settings.setAttribute( 'data-icon', '' );

							var screenOptionsLink = document.getElementById( 'show-settings-link' );
							settings.onclick = function() {
								screenOptionsLink.click();
							};

							metabox.insertBefore( settings, metabox.firstChild.nextSibling.nextSibling );

							wp.ccf.createSubmissionsTable( container );
						}
					});
				} else {
					SELF._setupMainModal( true );
					managerButton.style.display = 'inline-block';
				}
			} else {
				SELF._setupMainModal();
			}

			var managerClick = function( evnt ) {
				evnt = evnt || window.event;
				var target = ( evnt.currentTarget ) ? evnt.currentTarget : evnt.srcElement;
				var formId = target.getAttribute( 'data-form-id' );
				wp.ccf.toggle( formId );
			};

			if ( managerButton.addEventListener ) {
				managerButton.addEventListener( 'click', managerClick, false );
			} else {
				managerButton.attachEvent( 'onclick', managerClick );
			}
		}
	});

	wp.ccf.createManager();

})( jQuery, Backbone, _, ccfSettings );