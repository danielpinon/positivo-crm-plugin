/*
 * Bloco Gutenberg para inserir o formulário de agendamento do CRM Positivo.
 *
 * Este bloco permite que o administrador posicione o formulário em qualquer
 * local do conteúdo. O formulário em si é renderizado no lado do servidor
 * através do callback definido no registro do bloco em PHP. No editor, um
 * marcador simples é exibido indicando onde o formulário será carregado.
 */
( function( blocks, element, i18n ) {
    var el = element.createElement;
    blocks.registerBlockType( 'positivo-crm/agendamento', {
        title: i18n.__( 'Formulário de Agendamento Positivo CRM', 'positivo-crm' ),
        icon: 'calendar-alt',
        category: 'widgets',
        supports: {
            reusable: false,
        },
        edit: function() {
            return el( 'p', { className: 'positivo-crm-block-placeholder' }, i18n.__( 'Formulário de agendamento (renderizado no front‑end)', 'positivo-crm' ) );
        },
        save: function() {
            return null; // Renderização dinâmica em PHP
        },
    } );
} )( window.wp.blocks, window.wp.element, window.wp.i18n );