<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package    block_enrolcode
 * @copyright  2019 Center for Learning Management (http://www.lernmanagement.at)
 * @author     Robert Schrenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

class block_enrolcode_lib {
    const QR_OUTER_FRAME = 4;
    const QR_CIRCULAR_OUTER_FRAME = 0;
    const QR_LOGO_SCALE = 0.18;
    const QR_CIRCULAR_SCALE = 0.68;
    const QR_CIRCULAR_BORDER_SCALE = 0.07;

    public static $create_form_courseid = 0;

    /**
     * Check if the current user has capability enrol/manual:manage.
     * @param courseid (optional) if not given use the id from COURSE
     */
    public static function can_manage($courseid = 0) {
        global $COURSE;

        if (empty($courseid)) {
            $courseid = $COURSE->id;
        }
        $context = \context_course::instance($courseid);
        return has_capability('enrol/manual:manage', $context);
    }

    /**
     * Removes old entries from database.
     */
    public static function clean_db() {
        global $DB;
        // We remove any mature enrolcodes.
        $sql = "DELETE FROM {block_enrolcode}
                    WHERE (maturity>0 AND maturity<?)
                        OR (maturity=0 AND created<?)";
        $DB->execute($sql, array(time(), self::clean_ts()));
    }

    /**
     * Returns the timestamp for checking the validity.
     */
    public static function clean_ts() {
        return time() - 60 * 60;
    }

    /**
     * Create a code.
     * @param courseid (optional) the courseid, defaults to COURSE->id.
     * @param roleid (optional) the roleid, defaults to 3 (student).
     * @param groupid (optional) the groupid, defaults to 0.
     * @param custommaturity (optional) whether or not user wants a custom maturity.
     * @param maturity (optional) the maturity to set.
     * @param chkenrolmentend (optional) if an end of enrolment shall be set.
     * @param enrolmentend (optional) the timestamp when enrolments shall end.
     * @param nopermissioncheck (optional) true to suppress permission checks.
     * @return the code that was stored in the database.
     */
    public static function create_code($courseid = 0, $roleid = 0, $groupid = 0, $custommaturity = 0, $maturity = 0, $chkenrolmentend = 0, $enrolmentend = 0, $nopermissioncheck = false) {
        self::clean_db();
        global $COURSE, $DB, $USER;
        if (empty($courseid)) {
            $courseid = $COURSE->id;
        }
        if (empty($roleid)) {
            $roleid = 3;
        }
        $groupid = intval($groupid);
        if (!empty($groupid)) {
            // Check if that group exists and belongs to the course!
            $group = $DB->get_record('groups', array('id' => $groupid));
            if (empty($group->id) || $group->courseid != $courseid) {
                return '';
            }
        }
        $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
        if (!empty($course->id) && ($nopermissioncheck || self::can_manage($courseid))) {
            $codelength = 7;
            $chars = '0123456789abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNOPQRSTUVWXYZ';
            $code = '';
            for ($i = 0; $i < $codelength; $i++) {
                $code .= substr($chars, random_int(0, strlen($chars) - 1), 1);
            }

            $enrolcode = (object)array(
                'code' => $code,
                'courseid' => $courseid,
                'created' => time(),
                'maturity' => (!empty($custommaturity) && !empty($maturity)) ? $maturity : 0,
                'enrolmentend' => (!empty($chkenrolmentend) && !empty($enrolmentend)) ? $enrolmentend : 0,
                'roleid' => $roleid,
                'groupid' => $groupid,
                'userid' => $USER->id,
            );
            // check for duplicate code.
            $chkcode = $DB->get_record('block_enrolcode', array('code' => $enrolcode->code));
            if (!empty($chkcode->id)) {
                // Code already exists - we need another code.
                return self::create_code($courseid, $roleid, $groupid, $custommaturity, $maturity);
            } else {
                // We can store that code.
                $id = $DB->insert_record('block_enrolcode', $enrolcode, true);
                if (!empty($id) && $id > 0) {
                    return $enrolcode->code;
                } else {
                    return '';
                }
            }
        } else {
            return '';
        }
    }

    /**
     * Create the form in HTML.
     * @param courseid the courseid the form is built for.
     * @return the form in HTML.
     */
    public static function create_form($courseid) {
        self::$create_form_courseid = $courseid;
        require_once(__DIR__ . '/classes/code_form.php');
        $codeform = new code_form(null, null, 'post', '_self', array('class' => 'ui-enrolcode'), true);
        return $codeform->render();
    }

    /**
     * Delete a particular code.
     * @param code the code to delete.
     */
    public static function delete_code($code) {
        global $DB;

        self::clean_db();

        $code = $DB->get_record('block_enrolcode', ['code' => $code]);
        if (!$code) {
            return false;
        }

        if (!self::can_manage($code->courseid)) {
            return false;
        }

        $DB->delete_records('block_enrolcode', ['id' => $code->id]);
        return true;
    }

    /**
     * Check if the current user is enrolled in a course.
     * @param courseid (optional) if not given use the id from COURSE
     * @param withcapability (optional) only enrolments with a particular capability.
     */
    public static function is_enrolled($courseid = 0, $withcapability = "") {
        global $COURSE, $USER;

        if (empty($courseid)) {
            $courseid = $COURSE->id;
        }
        $context = \context_course::instance($courseid);
        return is_enrolled($context, $USER, $withcapability, true);
    }

    /**
     * Checks if a given code is valid and does the enrolment.
     */
    public static function enrol_by_code($code) {
        self::clean_db();
        global $CFG, $DB, $USER;

        if (!isloggedin() || isguestuser($USER)) {
            return 0;
        } else {
            if (!empty($_SESSION['last-enrolcode-used']) && $_SESSION['last-enrolcode-used'] > time() - 5) {
                // We do not allow to use a code twice within 5 seconds.
                return 0;
            }
            $_SESSION['last-enrolcode-used'] = time();

            $enrolcode = $DB->get_record_select('block_enrolcode',
                // case senstive search.
                $DB->sql_like('code', '?', true),
                [$code]);

            if ($enrolcode) {
                // Code is valid.
                $course = $DB->get_record('course', array('id' => $enrolcode->courseid), '*', MUST_EXIST);
                $context = context_course::instance($course->id);

                $enrol = enrol_get_plugin('manual');
                if ($enrol === null) {
                    return false;
                }
                $instances = enrol_get_instances($course->id, true);
                $manualinstance = null;
                foreach ($instances as $instance) {
                    if ($instance->enrol == 'manual') {
                        $manualinstance = $instance;
                        break;
                    }
                }

                if (empty($manualinstance->id)) {
                    $instanceid = $enrol->add_default_instance($course);
                    if ($instanceid === null) {
                        $instanceid = $enrol->add_instance($course);
                    }
                    $instance = $DB->get_record('enrol', array('id' => $instanceid));
                }

                $enrol->enrol_user($instance, $USER->id, $enrolcode->roleid, 0, $enrolcode->enrolmentend);

                if (!empty($enrolcode->groupid)) {
                    // Add user to the usergroup in the course.
                    require_once($CFG->dirroot . '/group/lib.php');
                    groups_add_member($enrolcode->groupid, $USER);
                }

                return $enrolcode->courseid;
            } else {
                return 0;
            }
        }
    }

    /**
     * Revokes a given code.
     */
    public static function revoke_code($code) {
        self::clean_db();
        global $DB, $USER;

        $enrolcode = $DB->get_record('block_enrolcode', array('code' => $code));

        if (empty($enrolcode->maturity)) {
            // We only revoke manually if we have no maturity.
            $DB->delete_records('block_enrolcode', array('code' => $code));
        }
    }

    /**
     * Outputs a QR code PNG using the current plugin settings.
     * @param string $text
     * @param string $preset
     */
    public static function output_qr_png($text, $preset = 'default') {
        require_once(__DIR__ . '/classes/phpqrcode/qrlib.php');

        $frame = QRcode::text($text, false, QR_ECLEVEL_H, 1, 0);
        $roundmodules = !empty(get_config('block_enrolcode', 'roundqrcode'));
        $circularqrcode = !empty(get_config('block_enrolcode', 'circularqrcode'));
        $outerframe = $circularqrcode ? self::QR_CIRCULAR_OUTER_FRAME : self::QR_OUTER_FRAME;
        $modulecount = strlen($frame[0]) + ($outerframe * 2);
        $targetpixels = self::get_qr_target_pixels($preset);
        $basepixels = $circularqrcode ? (int) floor($targetpixels * self::QR_CIRCULAR_SCALE) : $targetpixels;
        $foregroundcolour = self::get_qr_foreground_colour();

        $image = self::create_qr_image(
            $frame,
            max(48, $basepixels),
            $roundmodules,
            $foregroundcolour,
            $outerframe
        );
        $logofile = self::get_logo_file();
        if (!empty($logofile)) {
            self::apply_logo_to_qr($image, $logofile);
        }

        if ($circularqrcode) {
            $image = self::wrap_qr_in_circle($image, $targetpixels, $modulecount, $foregroundcolour);
        }

        header('Content-Type: image/png');
        header('Cache-Control: private, max-age=300');
        imagepng($image);
        imagedestroy($image);
    }

    /**
     * Returns the configured logo file if there is one.
     * @return stored_file|false
     */
    public static function get_logo_file() {
        $fs = get_file_storage();
        $context = context_system::instance();
        $files = $fs->get_area_files($context->id, 'block_enrolcode', 'logo', 0, 'itemid, filepath, filename', false);

        if (empty($files)) {
            return false;
        }

        return reset($files);
    }

    /**
     * Creates a QR image resource from a QR matrix.
     * @param array $frame
     * @param int $targetpixels
     * @param bool $roundmodules
     * @param array $foregroundcolour
     * @param int $outerframe
     * @return resource|GdImage
     */
    protected static function create_qr_image(array $frame, $targetpixels, $roundmodules = false, array $foregroundcolour = [0, 0, 0], $outerframe = self::QR_OUTER_FRAME) {
        $height = count($frame);
        $width = strlen($frame[0]);
        $modulecount = $width + ($outerframe * 2);
        $modulesize = max(2, (int) floor($targetpixels / $modulecount));
        $size = $modulecount * $modulesize;

        $image = imagecreatetruecolor($size, $size);
        $white = imagecolorallocate($image, 255, 255, 255);
        $ink = imagecolorallocate($image, $foregroundcolour[0], $foregroundcolour[1], $foregroundcolour[2]);
        imagefill($image, 0, 0, $white);

        if (function_exists('imageantialias')) {
            imageantialias($image, true);
        }

        $dotsize = max(2, (int) floor($modulesize * 0.78));

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                if ($frame[$y][$x] !== '1') {
                    continue;
                }

                $left = ($x + $outerframe) * $modulesize;
                $top = ($y + $outerframe) * $modulesize;

                if ($roundmodules && !self::is_finder_area($x, $y, $width, $height)) {
                    imagefilledellipse(
                        $image,
                        $left + (int) floor($modulesize / 2),
                        $top + (int) floor($modulesize / 2),
                        $dotsize,
                        $dotsize,
                        $ink
                    );
                    continue;
                }

                imagefilledrectangle(
                    $image,
                    $left,
                    $top,
                    $left + $modulesize - 1,
                    $top + $modulesize - 1,
                    $ink
                );
            }
        }

        return $image;
    }

    /**
     * Applies a centered logo to the QR image.
     * @param resource|GdImage $image
     * @param stored_file $logofile
     */
    protected static function apply_logo_to_qr($image, $logofile) {
        $contents = $logofile->get_content();
        if ($contents === false || $contents === '') {
            return;
        }

        $logo = @imagecreatefromstring($contents);
        if ($logo === false) {
            return;
        }

        $qrsize = min(imagesx($image), imagesy($image));
        $logosize = (int) round($qrsize * self::QR_LOGO_SCALE);
        $logosize = max(14, min($logosize, (int) round($qrsize * (self::QR_LOGO_SCALE + 0.06))));
        $centerx = (int) floor(imagesx($image) / 2);
        $centery = (int) floor(imagesy($image) / 2);

        $sourcewidth = imagesx($logo);
        $sourceheight = imagesy($logo);
        $scale = min($logosize / $sourcewidth, $logosize / $sourceheight);
        $targetwidth = max(1, (int) floor($sourcewidth * $scale));
        $targetheight = max(1, (int) floor($sourceheight * $scale));
        $targetleft = $centerx - (int) floor($targetwidth / 2);
        $targettop = $centery - (int) floor($targetheight / 2);
        $scaledlogo = imagecreatetruecolor($targetwidth, $targetheight);

        imagealphablending($scaledlogo, false);
        imagesavealpha($scaledlogo, true);
        $transparent = imagecolorallocatealpha($scaledlogo, 255, 255, 255, 127);
        imagefill($scaledlogo, 0, 0, $transparent);
        imagecopyresampled(
            $scaledlogo,
            $logo,
            0,
            0,
            0,
            0,
            $targetwidth,
            $targetheight,
            $sourcewidth,
            $sourceheight
        );

        imagealphablending($image, true);
        imagesavealpha($image, true);
        self::draw_logo_halo(
            $image,
            $targetleft,
            $targettop,
            $scaledlogo,
            max(3, (int) round($logosize * 0.11))
        );
        imagecopy(
            $image,
            $scaledlogo,
            $targetleft,
            $targettop,
            0,
            0,
            $targetwidth,
            $targetheight
        );
        imagedestroy($scaledlogo);
        imagedestroy($logo);
    }

    /**
     * Wraps a square QR code inside a circular badge.
     * @param resource|GdImage $image
     * @param int $targetpixels
     * @param int $modulecount
     * @param array $foregroundcolour
     * @return resource|GdImage
     */
    protected static function wrap_qr_in_circle($image, $targetpixels, $modulecount, array $foregroundcolour = [0, 0, 0]) {
        $basesize = max(imagesx($image), imagesy($image));
        $diameter = max($targetpixels, (int) ceil($basesize / self::QR_CIRCULAR_SCALE));
        $canvas = imagecreatetruecolor($diameter, $diameter);

        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 255, 255, 255, 127);
        imagefill($canvas, 0, 0, $transparent);

        if (function_exists('imageantialias')) {
            imageantialias($canvas, true);
        }

        $white = imagecolorallocate($canvas, 255, 255, 255);
        $ink = imagecolorallocate($canvas, $foregroundcolour[0], $foregroundcolour[1], $foregroundcolour[2]);
        $center = (int) floor($diameter / 2);
        $borderwidth = max(6, (int) round($diameter * self::QR_CIRCULAR_BORDER_SCALE));
        $qrleft = (int) floor(($diameter - imagesx($image)) / 2);
        $qrtop = (int) floor(($diameter - imagesy($image)) / 2);

        imagefilledellipse(
            $canvas,
            $center,
            $center,
            $diameter - 2,
            $diameter - 2,
            $ink
        );
        imagefilledellipse(
            $canvas,
            $center,
            $center,
            $diameter - ($borderwidth * 2),
            $diameter - ($borderwidth * 2),
            $white
        );

        imagealphablending($canvas, true);
        self::decorate_circular_frame(
            $canvas,
            $center,
            $diameter,
            $borderwidth,
            $qrleft,
            $qrtop,
            imagesx($image),
            $modulecount,
            $foregroundcolour
        );
        imagecopy(
            $canvas,
            $image,
            $qrleft,
            $qrtop,
            0,
            0,
            imagesx($image),
            imagesy($image)
        );
        imagedestroy($image);

        return $canvas;
    }

    /**
     * Draws a white halo that follows the logo shape.
     * @param resource|GdImage $image
     * @param int $left
     * @param int $top
     * @param resource|GdImage $logo
     * @param int $radius
     */
    protected static function draw_logo_halo($image, $left, $top, $logo, $radius) {
        $white = imagecolorallocate($image, 255, 255, 255);
        $width = imagesx($logo);
        $height = imagesy($logo);

        for ($offsety = -$radius; $offsety <= $radius; $offsety++) {
            for ($offsetx = -$radius; $offsetx <= $radius; $offsetx++) {
                if (($offsetx * $offsetx) + ($offsety * $offsety) > ($radius * $radius)) {
                    continue;
                }

                for ($y = 0; $y < $height; $y++) {
                    for ($x = 0; $x < $width; $x++) {
                        $pixel = imagecolorat($logo, $x, $y);
                        $alpha = ($pixel >> 24) & 0x7F;
                        if ($alpha > 90) {
                            continue;
                        }

                        $targetx = $left + $x + $offsetx;
                        $targety = $top + $y + $offsety;
                        if ($targetx < 0 || $targety < 0 || $targetx >= imagesx($image) || $targety >= imagesy($image)) {
                            continue;
                        }

                        imagesetpixel($image, $targetx, $targety, $white);
                    }
                }
            }
        }
    }

    /**
     * Adds decorative modules around a square QR inside a circular frame.
     * @param resource|GdImage $image
     * @param int $center
     * @param int $diameter
     * @param int $borderwidth
     * @param int $qrleft
     * @param int $qrtop
     * @param int $qrsize
     * @param int $modulecount
     * @param array $foregroundcolour
     */
    protected static function decorate_circular_frame($image, $center, $diameter, $borderwidth, $qrleft, $qrtop, $qrsize, $modulecount, array $foregroundcolour) {
        $modulesize = max(2, (int) floor($qrsize / max(1, $modulecount)));
        $innerradius = (int) floor(($diameter - ($borderwidth * 2)) / 2);
        $safeqrleft = $qrleft - $modulesize;
        $safeqrtop = $qrtop - $modulesize;
        $safeqrright = $qrleft + $qrsize + $modulesize;
        $safeqrbottom = $qrtop + $qrsize + $modulesize;
        $decorgb = self::get_decorative_colour($foregroundcolour);
        $deco = imagecolorallocate($image, $decorgb[0], $decorgb[1], $decorgb[2]);
        $startx = ((($qrleft % $modulesize) + $modulesize) % $modulesize) + (int) floor($modulesize / 2);
        $starty = ((($qrtop % $modulesize) + $modulesize) % $modulesize) + (int) floor($modulesize / 2);
        if ($startx >= $modulesize) {
            $startx -= $modulesize;
        }
        if ($starty >= $modulesize) {
            $starty -= $modulesize;
        }

        for ($gy = 0, $cy = $starty; $cy < $diameter; $gy++, $cy += $modulesize) {
            for ($gx = 0, $cx = $startx; $cx < $diameter; $gx++, $cx += $modulesize) {
                $dx = $cx - $center;
                $dy = $cy - $center;
                if (($dx * $dx) + ($dy * $dy) > (($innerradius - $modulesize) * ($innerradius - $modulesize))) {
                    continue;
                }

                if ($cx >= $safeqrleft && $cx <= $safeqrright && $cy >= $safeqrtop && $cy <= $safeqrbottom) {
                    continue;
                }

                $variant = (($gx * 11) + ($gy * 7)) % 8;
                if ($variant > 2) {
                    continue;
                }

                self::draw_decorative_module($image, $cx, $cy, $modulesize, $deco, $variant);
            }
        }
    }

    /**
     * Draws a decorative QR-style element.
     * @param resource|GdImage $image
     * @param int $centerx
     * @param int $centery
     * @param int $modulesize
     * @param int $colour
     * @param int $variant
     */
    protected static function draw_decorative_module($image, $centerx, $centery, $modulesize, $colour, $variant) {
        if ($variant === 0) {
            $dotsize = max(2, (int) round($modulesize * 0.48));
            imagefilledellipse($image, $centerx, $centery, $dotsize, $dotsize, $colour);
            return;
        }

        if ($variant === 1) {
            $width = max(3, (int) round($modulesize * 0.88));
            $height = max(2, (int) round($modulesize * 0.26));
            self::draw_rounded_box(
                $image,
                $centerx - (int) floor($width / 2),
                $centery - (int) floor($height / 2),
                $width,
                $height,
                $colour,
                max(1, (int) floor($height / 2))
            );
            return;
        }

        $width = max(2, (int) round($modulesize * 0.26));
        $height = max(3, (int) round($modulesize * 0.88));
        self::draw_rounded_box(
            $image,
            $centerx - (int) floor($width / 2),
            $centery - (int) floor($height / 2),
            $width,
            $height,
            $colour,
            max(1, (int) floor($width / 2))
        );
    }

    /**
     * Draws a rounded rectangle.
     * @param resource|GdImage $image
     * @param int $left
     * @param int $top
     * @param int $width
     * @param int $height
     * @param int $colour
     * @param int $radius
     */
    protected static function draw_rounded_box($image, $left, $top, $width, $height, $colour, $radius) {
        $radius = min($radius, (int) floor(min($width, $height) / 2));
        imagefilledrectangle($image, $left + $radius, $top, $left + $width - $radius - 1, $top + $height - 1, $colour);
        imagefilledrectangle($image, $left, $top + $radius, $left + $width - 1, $top + $height - $radius - 1, $colour);
        imagefilledellipse($image, $left + $radius, $top + $radius, $radius * 2, $radius * 2, $colour);
        imagefilledellipse($image, $left + $width - $radius - 1, $top + $radius, $radius * 2, $radius * 2, $colour);
        imagefilledellipse($image, $left + $radius, $top + $height - $radius - 1, $radius * 2, $radius * 2, $colour);
        imagefilledellipse($image, $left + $width - $radius - 1, $top + $height - $radius - 1, $radius * 2, $radius * 2, $colour);
    }

    /**
     * Finder patterns are kept square for scan reliability.
     * @param int $x
     * @param int $y
     * @param int $width
     * @param int $height
     * @return bool
     */
    protected static function is_finder_area($x, $y, $width, $height) {
        return ($x < 8 && $y < 8)
            || ($x >= ($width - 8) && $y < 8)
            || ($x < 8 && $y >= ($height - 8));
    }

    /**
     * Returns target image size for a QR usage preset.
     * @param string $preset
     * @return int
     */
    protected static function get_qr_target_pixels($preset) {
        switch ($preset) {
            case 'thumb':
                return 120;
            case 'modal':
                return 132;
            case 'full':
                return 300;
            default:
                return 160;
        }
    }

    /**
     * Returns the configured QR foreground colour as RGB.
     * @return int[]
     */
    protected static function get_qr_foreground_colour() {
        $colour = (string) get_config('block_enrolcode', 'qrforegroundcolour');
        if (preg_match('/^#?([a-f0-9]{6})$/i', $colour, $matches) !== 1) {
            return [0, 0, 0];
        }

        $hex = $matches[1];
        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }

    /**
     * Returns a softened decorative tone based on the QR foreground colour.
     * @param array $foregroundcolour
     * @return int[]
     */
    protected static function get_decorative_colour(array $foregroundcolour) {
        $blend = function($value) {
            return (int) round($value + ((255 - $value) * 0.55));
        };

        return [
            $blend($foregroundcolour[0]),
            $blend($foregroundcolour[1]),
            $blend($foregroundcolour[2]),
        ];
    }
}
