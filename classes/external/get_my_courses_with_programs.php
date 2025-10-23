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

namespace local_categoryfields\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_description;
use core_external\external_single_structure;
use core_external\external_multiple_structure;

/**
 * Classe do Web Service para obter cursos com dados de programa.
 */
class get_my_courses_with_programs extends \core_external\external_api {

    /**
     * Define os parâmetros que nosso web service aceita.
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters(
            [
                'classification' => new external_value(
                    PARAM_ALPHANUMEXT,
                    'Filtro de classificação (ex: inprogress, past, future)',
                    VALUE_OPTIONAL,
                    'inprogress'
                ),
                'sort' => new external_value(PARAM_ALPHANUMEXT, 'Ordenação', VALUE_OPTIONAL, 'fullname'),
                'limit' => new external_value(PARAM_INT, 'Limite de cursos', VALUE_OPTIONAL, 0),
                'offset' => new external_value(PARAM_INT, 'Offset', VALUE_OPTIONAL, 0),
                'customfieldname' => new external_value(PARAM_RAW, 'Campo customizado', VALUE_OPTIONAL),
                'customfieldvalue' => new external_value(PARAM_RAW, 'Valor do campo customizado', VALUE_OPTIONAL),
            ]
        );
    }

    /**
     * A função principal que faz o trabalho.
     */
    public static function execute(
        $classification = 'inprogress',
        $sort = 'fullname',
        $limit = 0,
        $offset = 0,
        $customfieldname = null,
        $customfieldvalue = null
    ): array {
        global $DB, $CFG, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'classification' => $classification,
            'sort' => $sort,
            'limit' => $limit,
            'offset' => $offset,
            'customfieldname' => $customfieldname,
            'customfieldvalue' => $customfieldvalue,
        ]);

        $context = \context_user::instance($USER->id);
        self::validate_context($context);

        require_once($CFG->dirroot . '/course/externallib.php');
        $courses_data = \core_course_external::get_enrolled_courses_by_timeline_classification(
            $params['classification'],
            $params['limit'],
            $params['offset'],
            $params['sort'],
            $params['customfieldname'],
            $params['customfieldvalue']
        );

        foreach ($courses_data['courses'] as $index => $course) {
            $course_array = (array)$course;
            $program_cat = null;

            $course_category_id = $DB->get_field('course', 'category', ['id' => $course->id], IGNORE_MISSING);

            if ($course_category_id) {
                $course_category = \core_course_category::get($course_category_id, IGNORE_MISSING);
                
                if ($course_category) {
                    $ids_to_check = $course_category->get_parents();
                    
                    $ids_to_check[] = $course_category->id;
                    $ids_to_check = array_unique($ids_to_check);

                    list($sql_in, $params_in) = $DB->get_in_or_equal($ids_to_check, SQL_PARAMS_NAMED, 'catid');
                    
                    $sql = "SELECT c.id, c.name
                            FROM {course_categories} c
                            JOIN {local_categoryfields_data} lcf ON c.id = lcf.categoryid
                            WHERE c.id $sql_in
                              AND lcf.is_program = :isprogram
                            ORDER BY c.depth ASC";

                    $params_in['isprogram'] = 1;
                    
                    $record = $DB->get_record_sql($sql, $params_in);
                    
                    if ($record) {
                        $program_cat = [
                            'id' => (int)$record->id,
                            'name' => $record->name
                        ];
                    }
                }
            }
            
            $course_array['program_category'] = $program_cat;
            $courses_data['courses'][$index] = (object)$course_array;
        }

        return $courses_data;
    }
    
    /**
     * Define o que nossa função retorna.
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure(
            [
                'courses' => new external_multiple_structure(
                    new external_single_structure(
                        [
                            'id' => new external_value(PARAM_INT, 'ID do curso'),
                            'shortname' => new external_value(PARAM_TEXT, 'Nome curto'),
                            'fullname' => new external_value(PARAM_TEXT, 'Nome completo'),
                            'category' => new external_value(PARAM_INT, 'ID da categoria', VALUE_OPTIONAL),
                            'program_category' => new external_single_structure(
                                [
                                    'id' => new external_value(PARAM_INT, 'ID do programa', VALUE_OPTIONAL),
                                    'name' => new external_value(PARAM_TEXT, 'Nome do programa', VALUE_OPTIONAL)
                                ],
                                'Categoria do programa (objeto ou nulo)',
                                VALUE_OPTIONAL
                            )
                        ],
                        'Estrutura de um curso',
                        VALUE_OPTIONAL
                    )
                ),

                'nextoffset' => new external_value(PARAM_INT, 'Offset para a próxima página')
            ]
        );
    }
}