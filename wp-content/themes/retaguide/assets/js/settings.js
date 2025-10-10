(function($){
	function updateField(){
		var ids = [];
		$('#retaguide-pinned-list li').each(function(){
			ids.push($(this).data('id'));
		});
		$('#retaguide-pinned-field').val(ids.join(','));
	}

	$(document).ready(function(){
		var $list = $('#retaguide-pinned-list');
		if(!$list.length){
			return;
		}

		$list.sortable({
			axis:'y',
			update:updateField
		});

		$list.on('click','.delete',function(){
			$(this).closest('li').remove();
			updateField();
		});

		$('#retaguide-add-pinned').on('click',function(){
			var id = parseInt($('#retaguide-pinned-id').val(),10);
			if(!id){
				return;
			}

			var exists = $list.find('li[data-id="'+id+'"]').length;
			if(exists){
				$('#retaguide-pinned-id').val('');
				return;
			}

			$.post(ajaxurl,{
				action:'retaguide_lookup_post',
				id:id,
				nonce:retaguideSettings.nonce
			}).done(function(response){
				if(!response.success){
					window.alert(retaguideSettings.labels.failed);
					return;
				}
				var data=response.data;
				var item=$('<li/>',{'data-id':data.id});
				item.append($('<span/>',{'class':'label',text:data.title+' ('+data.type+')'}));
				item.append($('<button/>',{'type':'button','class':'button-link delete','aria-label':'Remove'}).text('×'));
				$list.append(item);
				$('#retaguide-pinned-id').val('');
				updateField();
			}).fail(function(){
				window.alert(retaguideSettings.labels.failed);
			});
		});
	});
})(jQuery);
