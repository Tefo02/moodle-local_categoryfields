<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

require_once(__DIR__ . '/lib.php');
require_once(__DIR__ . '/classes/form/edit_category_fields_form.php');

function local_categoryfields_extend_navigation_category_settings(navigation_node $parentnode, context_coursecat $context) {
    if (!has_capability('moodle/category:manage', $context)) {
        return;
    }
    $categoryid = $context->instanceid;
    $url = new moodle_url('/local/categoryfields/edit.php', ['categoryid' => $categoryid]);
    $parentnode->add(
        get_string('pluginname', 'local_categoryfields'),
        $url,
        navigation_node::NODETYPE_LEAF,
        null,
        'local_categoryfields',
        new pix_icon('i/settings', '')
    );
}

/**
 * Serves the files from the local_categoryfields plugin file area.
 *
 * @param stdClass $course The course object
 * @param stdClass $cm The course module object
 * @param context $context The context
 * @param string $filearea The name of the file area
 * @param array $args The arguments
 * @param bool $forcedownload Whether to force a download
 * @param array $options Additional options
 * @return bool
 * @package local_categoryfields
 */
function local_categoryfields_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    if ($filearea !== 'category_image') {
        return false;
    }

    $itemid = array_shift($args);
    $filename = array_pop($args);

    if ($context->contextlevel != CONTEXT_COURSECAT) {
        return false;
    }

    $category = \core_course_category::get($context->instanceid, IGNORE_MISSING);
    if (!$category || !$category->is_uservisible()) {
        return false;
    }

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'local_categoryfields', $filearea, $itemid, '/', $filename);

    if (!$file) {
        return false;
    }

    send_stored_file($file, 0, 0, $forcedownload, $options);
}


/**
 * Returns an array of related categories for a given category ID.
 *
 * @param int $categoryid The ID of the category.
 * @return array An array of \core_course_category objects.
 * @package local_categoryfields
 */
function local_categoryfields_get_related_categories(int $categoryid): array {
    global $DB;

    $record = $DB->get_record(
        'local_categoryfields_data',
        ['categoryid' => $categoryid],
        'related_categories',
        IGNORE_MISSING
    );

    if (!$record || empty($record->related_categories)) {
        return [];
    }

    $relatedcategoryids = explode(',', $record->related_categories);
    $relatedcategories = [];

    foreach ($relatedcategoryids as $id) {
        $cat = \core_course_category::get((int)$id, IGNORE_MISSING);
        if ($cat && $cat->is_uservisible()) {
            $cat->full_path = $cat->get_formatted_name();
            $relatedcategories[] = $cat;
        }
    }

    return $relatedcategories;
}

/**
 * Retorna um array com os IDs de TODOS os descendentes de uma categoria (filhos, netos, etc.).
 * Esta função é recursiva por natureza, mas implementada de forma iterativa para performance.
 *
 * @param \core_course_category $category A categoria raiz da qual buscar os descendentes.
 * @return array Um array plano com todos os IDs dos descendentes.
 * @package local_categoryfields
 */
function local_categoryfields_get_all_descendant_ids(\core_course_category $category): array {
    $descendantids = [];
    $queue = $category->get_children();
    while (!empty($queue)) {
        $currentcat = array_shift($queue);
        $descendantids[] = $currentcat->id;
        $grandchildren = $currentcat->get_children();
        if (!empty($grandchildren)) {
            $queue = array_merge($queue, $grandchildren);
        }
    }
    return $descendantids;
}

function local_categoryfields_get_category_path_name(\core_course_category $category, int $excludetreerootid = 0): string {
    global $DB;
    $parentids = $category->get_parents();
    if ($excludetreerootid > 0) {
        $key = array_search($excludetreerootid, $parentids);
        if ($key !== false) {
            $parentids = array_slice($parentids, $key + 1);
        } else if ($category->id == $excludetreerootid) {
            return $category->name;
        }
    }
    if (empty($parentids)) {
        return $category->name;
    }
    $parents = $DB->get_records_list('course_categories', 'id', $parentids);
    $pathnames = [];
    foreach ($parentids as $id) {
        if (isset($parents[$id])) {
            $pathnames[] = $parents[$id]->name;
        }
    }
    $pathnames[] = $category->name;
    return implode(' / ', $pathnames);
}

/**
 * Informa ao Moodle que queremos adicionar campos dinâmicos ao contexto 'mycourses'.
 *
 * @param string $context O contexto (ex: 'mycourses').
 * @return array
 */
function local_categoryfields_course_list_dynamic_fields(string $context): array {
    if ($context === 'mycourses') {
        return ['program_category'];
    }
    return [];
}

/**
 * Popula o valor do nosso campo dinâmico para um curso específico.
 *
 * @param stdClass $course O objeto do curso.
 * @param string $fieldname O nome do campo ('program_category').
 * @param string $context O contexto ('mycourses').
 * @return mixed
 */
function local_categoryfields_course_list_dynamic_field_value(
    stdClass $course, 
    string $fieldname, 
    string $context
) {
    global $DB;

    if ($context !== 'mycourses' || $fieldname !== 'program_category') {
        return null;
    }

    $course_category = core_course_category::get($course->category, IGNORE_MISSING);
    if (!$course_category) {
        return null;
    }

    // 2. Obtém todos os pais dessa categoria (ex: "Módulo 1" -> "Ciência da Computação")
    // O método get_parent_categories() retorna um array de stdClass [id => obj]
    $parent_categories = $course_category->get_parent_categories();
    // Precisamos incluir a própria categoria do curso na verificação? 
    // Provavelmente não, mas se sim: $parent_categories[$course_category->id] = $course_category;
    
    if (empty($parent_categories)) {
        return null;
    }

    $parent_ids = array_keys($parent_categories);

    // 3. Verifica qual desses pais está marcado como 'is_program'
    // Esta SQL assume que sua tabela se chama {local_categoryfields_data}
    // e os campos são {categoryid} e {is_program}.
    // **AJUSTE OS NOMES DA TABELA E CAMPO CONFORME SUA IMPLEMENTAÇÃO**
    $sql = "SELECT c.id, c.name
            FROM {course_categories} c
            JOIN {local_categoryfields_data} lcf ON c.id = lcf.categoryid
            WHERE c.id IN (" . implode(',', $parent_ids) . ")
              AND lcf.is_program = 1
            ORDER BY c.depth ASC"; // Pega o "Programa" de nível mais alto

    $program_cat = $DB->get_record_sql($sql);

    if ($program_cat) {
        // Sucesso! Injeta um objeto com os dados do programa.
        return ['id' => (int)$program_cat->id, 'name' => $program_cat->name];
    }

    return null; // Este curso não pertence a um programa
}