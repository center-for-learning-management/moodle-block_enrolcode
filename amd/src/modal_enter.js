/*
 * @package    block_enrolcode
 * @copyright  2020 Center for learning management (www.lernmanagement.at)
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 /**
  * @module block_enrolcode/modal_enter
  */
  define(['jquery', 'core/notification', 'core/custom_interaction_events', 'core/modal', 'core/modal_registry'],
        function($, Notification, CustomEvents, Modal, ModalRegistry) {

    var registered = false;
    var SELECTORS = {
        OK_BUTTON: '[data-action="ok"]',
    };

    /**
     * Constructor for the Modal.
     *
     * @param {object} root The root jQuery element for the modal
     */
    var ModalEnter = function(root) {
        Modal.call(this, root);

        if (!this.getFooter().find(SELECTORS.OK_BUTTON).length) {
            Notification.exception({message: 'No ok button found'});
        }
    };

    ModalEnter.TYPE = 'block_enrolcode-enter';
    ModalEnter.prototype = Object.create(Modal.prototype);
    ModalEnter.prototype.constructor = ModalEnter;

    /**
     * Set up all of the event handling for the modal.
     *
     * @method registerEventListeners
     */
    ModalEnter.prototype.registerEventListeners = function() {
        // Apply parent event listeners.
        Modal.prototype.registerEventListeners.call(this);

        this.getModal().on(CustomEvents.events.activate, SELECTORS.OK_BUTTON, function(e, data) {
            // Add your logic for when the login button is clicked. This could include the form validation,
            // loading animations, error handling etc.
            var code = $(this.getRoot()).find('#code').val();
            require(['block_enrolcode/main'], function(MAIN) { MAIN.sendCode('', code); } );
            console.log(this);
            this.hide();
        }.bind(this));
    };

    // Automatically register with the modal registry the first time this module is imported so that you can create modals
    // of this type using the modal factory.
    if (!registered) {
        ModalRegistry.register(ModalEnter.TYPE, ModalEnter, 'block_enrolcode/modal_enter');
        registered = true;
    }

    return ModalEnter;
});
