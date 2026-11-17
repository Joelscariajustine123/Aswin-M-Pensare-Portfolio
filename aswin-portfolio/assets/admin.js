jQuery(function ($) {
	function sync(field) {
		var ids = field.find('.aswin-thumb').map(function () { return $(this).data('id'); }).get();
		field.find('input[type=hidden]').val(ids.join(','));
		field.find('.aswin-clear').prop('hidden', !ids.length);
	}

	function thumb(a) {
		var inner;
		if (a.type === 'image') {
			var url = a.sizes && a.sizes.thumbnail ? a.sizes.thumbnail.url : a.url;
			inner = $('<img>').attr('src', url);
		} else {
			inner = $('<span class="aswin-file">').text((a.type === 'video' ? '🎬 ' : '📄 ') + a.filename);
		}
		return $('<span class="aswin-thumb">').attr('data-id', a.id).append(inner, '<button type="button" class="aswin-x" aria-label="Remove">×</button>');
	}

	$(document).on('click', '.aswin-pick', function (e) {
		e.preventDefault();
		var btn = $(this), field = btn.closest('.aswin-field'), multiple = btn.data('multiple') == 1, type = btn.data('type');
		var frame = wp.media({
			title: btn.data('title'),
			multiple: multiple ? 'add' : false,
			library: type ? { type: type } : {},
			button: { text: 'Use ' + (multiple ? 'these' : 'this') }
		});
		frame.on('select', function () {
			var sel = frame.state().get('selection').toJSON();
			var preview = field.find('.aswin-preview');
			if (!multiple) { preview.empty(); sel = sel.slice(0, 1); }
			sel.forEach(function (a) {
				if (!preview.find('[data-id="' + a.id + '"]').length) preview.append(thumb(a));
			});
			sync(field);
		});
		frame.open();
	});

	$(document).on('click', '.aswin-x', function (e) {
		e.preventDefault();
		var field = $(this).closest('.aswin-field');
		$(this).closest('.aswin-thumb').remove();
		sync(field);
	});

	$(document).on('click', '.aswin-clear', function (e) {
		e.preventDefault();
		var field = $(this).closest('.aswin-field');
		field.find('.aswin-preview').empty();
		sync(field);
	});

	// Drag to reorder gallery images.
	if ($.fn.sortable) {
		$('.aswin-media.is-multiple .aswin-preview').sortable({ update: function () { sync($(this).closest('.aswin-field')); } });
	}
});
