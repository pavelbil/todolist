jQuery(function ($) {
	'use strict';

	var App = {
		init: function() {
			this.bindEvents();
			this.render();
			this.listId = $('.todoapp').data('list-id');
		},
		bindEvents: function() {
			$('.todo-list')
					//destroy item
					.on('click', '.destroy', this.destroy.bind(this))
					//edit
					.on('dblclick', '.view label', this.edit.bind(this))
					//on edit keyup
					.on('keyup', '.edit', this.editKeyup.bind(this))
					//on focus out edit input
					.on('focusout', '.edit', this.update.bind(this))
					//on change check item
					.on('change', '.toggle', this.toggle.bind(this));

			//create new item
			$(document).on('keyup', '.new-todo', this.create.bind(this));

			//check all items
			$(document).on('change', '.toggle-all', this.toggleAll.bind(this));

			//clear completed items
			$(document).on('click', '.clear-completed', this.clearCompleted.bind(this));

			//filter
			$(document).on('click', '.filters li a', this.changeFilter.bind(this));

		},
		render: function() {
			this.renderFilters();

			var itemList = $('.toggle'),
					isAllChecked = itemList.length ? true : false;

			itemList.each(function(key, item) {
				if(!$(item).is(':checked')) {
					isAllChecked = false;
					return true;
				}
			});

			$('.toggle-all').prop('checked', isAllChecked);
		},
		changeFilter: function(event) {
			event.preventDefault();
			var target = $(event.target),
					filter = target.data('filter');

			if(target.hasClass('selected')) {
				return false;
			}

			$('.filters a.selected').removeClass('selected');
			target.addClass('selected');
			this.showAll();

			switch(filter) {
				case 'active':
					$('.toggle:checked').closest('li').hide();
					break;
				case 'completed':
					$('.toggle:not(:checked)').closest('li').hide();
					break;
			}
		},
		showAll: function () {
			$('.todo-list li').show();
		},
		renderCounter: function() {
			var itemCount = $('.toggle:not(:checked)').length,
					counter = '<strong>' + itemCount + '</strong> ' + this.pluralize(itemCount, 'item') + ' left';

			$('.todo-count').html(counter);
		},
		renderFilters: function() {
			var footer = $('.footer');
			footer.hide();
			if($('.todo-list li').length) {
				footer.show();
				this.renderCounter();
			}
		},
		clearCompleted: function() {
			var idList = [];
			$('.todo-list li.completed').each(function(key, item) {
				$(item).find('.destroy').trigger('click');
			});
			this.renderFilters();
		},
		destroy: function(event) {
			event.preventDefault();
			var id = $(event.target).closest('li').data('id');

			this.delete(id, function() {
				$('[data-id=' + id +']').remove();
			});
		},
		edit: function(event) {
			var input = $(event.target).closest('li').addClass('editing').find('.edit');
			input.val(input.val()).focus();
			this.currentValue = input.val();
		},
		editKeyup: function(event) {
			var target = $(event.target),
					editInput = target.find('.edit');

			//on enter
			if(event.which == 13) {
				target.blur().closest('li').removeClass('editing');
				target.val() ? this.update(event) : this.destroy(event);
			}

			//on esc
			if(event.which == 27) {
				target.val(this.currentValue).blur().closest('li').removeClass('editing');
			}
		},
		update: function(event) {
			var target = $(event.target),
					value = target.data('is-canceled') ? this.currentValue : target.val(),
					id = target.closest('li').data('id');

			this.save({
				id: id,
				name: value
			}, this.updateCallback);
		},
		updateCallback: function(data) {
			var item_element,
					complete_check;


			item_element = $('[data-id=' + data.id +']').removeClass('editing');
			item_element.removeClass('editing').find('.view label').text(data.name);
			complete_check = item_element.find('.toggle');

			if(complete_check.prop('checked') != data.is_completed) {
				complete_check.trigger('change');
			}

			$('.new-todo').focus();
		},
		create: function(event) {
			var target = $(event.target),
					value = target.val().trim();

			if(event.which == 13 && value) {
				target.val('');
				this.save({name: value}, this.addNewCallback.bind(this));
			}
		},
		addNewCallback: function(data) {
			if(data) {
				var newItem =
						'<li data-id="' + data.id + '">' +
						'<div class="view">' +
						'<input class="toggle" type="checkbox">' +
						'<label>' + data.name + '</label>' +
						'<button class="destroy"></button>' +
						'</div>' +
						'<input class="edit" value=' + data.name + '>' +
						'</li>';
				$('.todo-list').append(newItem);
				$('.toggle-all').prop('checked', false);
			}
			this.render();
		},
		toggle: function(event) {
			var target = $(event.target),
					todo = target.closest('li'),
					isAllChecked = true,
					id = target.closest('li').data('id'),
					is_completed = target.is(':checked');

			this.save({
				id: id,
				is_completed: is_completed
			}, function() {});

			target.is(':checked') ? todo.addClass('completed') : todo.removeClass('completed');

			this.render();
		},
		afterCheckCallback: function (data) {

		},
		toggleAll: function (event) {
			var is_checked = $(event.target).is( ":checked"),
					itemList = is_checked ? $('.toggle:not(:checked)') : $('.toggle:checked');

			itemList.each(function(key, item) {
				$(item).prop('checked', is_checked).trigger('change');
			});
			this.render();
		},
		save: function(task, callback) {
			task['list_id'] = this.listId;
			$.ajax({
				url: '/task/save',
				method: 'POST',
				data: task,
				dataType: 'json',
				success: function(response) {
					if(response.status == 1) {
						callback(response.data);
					} else {
						$('.js-error-block').html(response.error).removeClass('hidden');
					}
				}
			});
		},
		delete: function(id, callback) {
			$.ajax({
				url: '/task/delete',
				method: 'POST',
				data: {
					id: id
				},
				dataType: 'json',
				success: function(response) {
					if(response.status == 1) {
						callback(response.data);
					} else {
						$('.js-error-block').html(response.error).removeClass('hidden');
					}
				}
			});
		},
		pluralize: function(num, word) {
			return num === 1 ? word : word + 's';
		}
	};

	App.init();
});
