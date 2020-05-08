/*
 * @package    block_enrolcode
 * @copyright  2020 Center for learning management (www.lernmanagement.at)
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 /**
  * @module block_enrolcode/modal_code
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
    var ModalCode = function(root) {
        Modal.call(this, root);

        if (!this.getFooter().find(SELECTORS.OK_BUTTON).length) {
            Notification.exception({message: 'No ok button found'});
        }
    };

    ModalCode.TYPE = 'block_enrolcode-code';
    ModalCode.prototype = Object.create(Modal.prototype);
    ModalCode.prototype.constructor = ModalCode;

    /**
     * Set up all of the event handling for the modal.
     *
     * @method registerEventListeners
     */
    ModalCode.prototype.registerEventListeners = function() {
        // Apply parent event listeners.
        Modal.prototype.registerEventListeners.call(this);

        this.getModal().on(CustomEvents.events.activate, SELECTORS.OK_BUTTON, function(e, data) {
            // Add your logic for when the login button is clicked. This could include the form validation,
            // loading animations, error handling etc.
            var code = $(this.getRoot()).find('#code').html();
            require(['block_enrolcode/main'], function(MAIN) { MAIN.revokeCode(code)});
            console.log(this);
            this.hide();
        }.bind(this));
    };

    // Automatically register with the modal registry the first time this module is imported so that you can create modals
    // of this type using the modal factory.
    if (!registered) {
        ModalRegistry.register(ModalCode.TYPE, ModalCode, 'block_enrolcode/modal_code');
        registered = true;
    }

    return ModalCode;
});
