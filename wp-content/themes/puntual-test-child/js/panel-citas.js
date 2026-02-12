jQuery(document).ready(function ($) {

    // --- Lógica del Panel de Gestión de Citas ---
    
    if ($('.citas-panel-wrap').length === 0) {
        return;
    }

    $('#cita-modal').appendTo('body');
    
    const modal = $('#cita-modal');
    const modalBody = $('#cita-modal-body');
    const modalClose = $('.cita-modal-close');

    function closeModal() {
        modal.fadeOut(200);
    }

    $('#citas-list').on('click', '.btn-details', function () {
        const card = $(this).closest('.cita-card');
        const citaId = card.data('id');
        const detailsHtml = card.find('.cita-full-details').html();
        const fechaRaw = card.find('.cita-full-details').data('fecha-raw');
        const horaRaw = card.find('.cita-full-details').data('hora-raw');

        let modalContent = `
            <div class="cita-modal-view" data-id="${citaId}" data-fecha-raw="${fechaRaw}" data-hora-raw="${horaRaw}">
                <h2>Detalles de la Cita</h2>
                <div class="details-content">${detailsHtml}</div>
                <div class="modal-actions">
                     <button class="button btn-modal-confirm" title="Marcar como confirmada">✔️ Confirmar</button>
                     <button class="button btn-modal-edit" title="Cambiar fecha y hora">✏️ Modificar</button>
                     <button class="button btn-modal-cancel-cita" title="Marcar como cancelada">❌ Cancelar Cita</button>
                </div>
            </div>
        `;
        modalBody.html(modalContent);
        modal.fadeIn(200);
    });

    modal.on('click', '.btn-modal-edit', function() {
        const modalView = $(this).closest('.cita-modal-view');
        const citaId = modalView.data('id');
        const fechaRaw = modalView.data('fecha-raw');
        const horaRaw = modalView.data('hora-raw');

        let editFormHtml = `
            <h2>Modificar Fecha y Hora</h2>
            <div class="cita-edit-form">
                <div class="form-group">
                    <label for="edit-fecha">Nueva Fecha:</label>
                    <input type="date" id="edit-fecha" value="${fechaRaw}">
                </div>
                <div class="form-group">
                    <label for="edit-hora">Nueva Hora:</label>
                    <input type="time" id="edit-hora" value="${horaRaw}">
                </div>
                <div class="form-group">
                    <label for="edit-observacion">Razón del cambio (opcional):</label>
                    <textarea id="edit-observacion" rows="3" placeholder="Ej: Cliente solicitó cambio..."></textarea>
                </div>
                <div class="modal-actions">
                    <button class="button button-primary btn-modal-save" data-id="${citaId}">Guardar Cambios</button>
                    <button class="button btn-modal-cancel">Cancelar</button>
                </div>
            </div>
        `;
        modalView.html(editFormHtml);
    });
    
    modal.on('click', '.btn-modal-cancel', function() { closeModal(); });

    modal.on('click', '.btn-modal-save', function() {
        const saveButton = $(this);
        const citaId = saveButton.data('id');
        const nuevaFecha = $('#edit-fecha').val();
        const nuevaHora = $('#edit-hora').val();
        const observacion = $('#edit-observacion').val();

        $.ajax({
            url: citas_ajax.ajax_url,
            type: 'POST',
            dataType: 'json', // <--- CORREGIDO
            data: { 
                action: 'edit_cita_fecha_admin', 
                cita_id: citaId, 
                nueva_fecha: nuevaFecha, 
                nueva_hora: nuevaHora, 
                observacion: observacion, 
                nonce: citas_ajax.nonce
            },
            beforeSend: function() { saveButton.text('Guardando...').prop('disabled', true); },
            success: function(response) {
                if (response.success) {
                   alert('Cita actualizada con éxito. La página se recargará.');
                   location.reload();
                } else {
                    alert('Error: ' + (response.data.message || 'No se pudo actualizar.'));
                    saveButton.text('Guardar Cambios').prop('disabled', false);
                }
            },
            error: function() {
                alert('Ocurrió un error de conexión.');
                saveButton.text('Guardar Cambios').prop('disabled', false);
            }
        });
    });

    modal.on('click', '.btn-modal-confirm, .btn-modal-cancel-cita', function() {
        const button = $(this);
        const citaId = button.closest('.cita-modal-view').data('id');
        const nuevoEstado = button.hasClass('btn-modal-confirm') ? 'confirmada' : 'cancelada';
        let observacion = '';

        if (nuevoEstado === 'cancelada') {
            observacion = prompt('Por favor, introduce una razón para la CANCELACIÓN:');
            if (observacion === null) return;
        } else {
            observacion = prompt('Añade una nota de CONFIRMACIÓN (opcional):', 'Cita confirmada vía telefónica.');
            if (observacion === null) return;
        }

        $.ajax({
            url: citas_ajax.ajax_url,
            type: 'POST',
            dataType: 'json', // <--- CORREGIDO
            data: { 
                action: 'update_cita_status', 
                cita_id: citaId, 
                nuevo_estado: nuevoEstado, 
                observacion: observacion, 
                nonce: citas_ajax.nonce
            },
            beforeSend: function() { button.closest('.modal-actions').find('button').prop('disabled', true); },
            success: function(response) {
                if (response.success) {
                    alert('Estado actualizado. La página se recargará.');
                    location.reload();
                } else {
                    alert('Error: ' + (response.data.message || 'No se pudo actualizar.'));
                    button.closest('.modal-actions').find('button').prop('disabled', false);
                }
            },
            error: function() {
                alert('Ocurrió un error de conexión.');
                button.closest('.modal-actions').find('button').prop('disabled', false);
            }
        });
    });

    $('#citas-list').on('click', '.btn-delete', function () {
        if (!confirm('¿Estás seguro? Esta acción no se puede deshacer.')) { return; }
        const card = $(this).closest('.cita-card');
        const citaId = card.data('id');
        
        $.ajax({
            url: citas_ajax.ajax_url,
            type: 'POST',
            dataType: 'json', // <--- CORREGIDO
            data: { 
                action: 'delete_cita', 
                cita_id: citaId, 
                nonce: citas_ajax.nonce
            },
            beforeSend: function () { card.css('opacity', '0.5'); },
            success: function (response) {
                if (response.success) { 
                    card.fadeOut(400, function() { $(this).remove(); });
                } else { 
                    alert(response.data.message || 'Error al eliminar la cita.'); 
                    card.css('opacity', '1'); 
                }
            },
            error: function () { 
                alert('Ocurrió un error de conexión.'); 
                card.css('opacity', '1'); 
            }
        });
    });

    modalClose.on('click', closeModal);
    modal.on('click', function (e) { if ($(e.target).is(modal)) { closeModal(); } });
    $(document).on('keyup', function(e) { if (e.key === "Escape") { closeModal(); } });
    
    $('#citas-search').on('keyup', function() {
        const searchTerm = $(this).val().toLowerCase();
        $('.cita-card').each(function () {
            const card = $(this);
            const nombre = card.find('.cita-nombre').text().toLowerCase();
            const email = card.find('.cita-email').text().toLowerCase();
            if (nombre.includes(searchTerm) || email.includes(searchTerm)) {
                card.show();
            } else {
                card.hide();
            }
        });
    });
});