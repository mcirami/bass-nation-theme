<?php

namespace Kainex\WiseChatPro\DAO;

/**
 * Abstract DAO
 *
 * @author Kainex <contact@kainex.pl>
 */
abstract class AbstractDAO {

	/**
	 * @param array $tableRow
	 * @return int ID of the object
	 */
	protected function persist(array $tableRow): int {
		global $wpdb;

		if (isset($tableRow['id'])) {
			$id = $tableRow['id'];
			unset($tableRow['id']);
			$wpdb->update($this->getTableName(), $tableRow, array('id' => $id), '%s', '%d');

			return $id;
		} else {
			$wpdb->insert($this->getTableName(), $tableRow);

			return $wpdb->insert_id;
		}
	}

	/**
     * @param integer $id
     */
    public function deleteById(int $id) {
        global $wpdb;

        $wpdb->query($wpdb->prepare("DELETE FROM %i WHERE `id` = %d;", $this->getTableName(), $id));
    }

	/**
     * @param array $conditions
     */
    protected function deleteBy(array $conditions) {
        global $wpdb;

        $wpdb->query($wpdb->prepare("DELETE FROM %i WHERE ".$this->prepareConditions($conditions), $this->getTableName()));
    }

	/**
	 * @param array $conditions
	 * @return object|null
	 */
	protected function getOneBy(array $conditions): ?object {
		global $wpdb;

		$sql = $wpdb->prepare('SELECT * FROM %i WHERE '.$this->prepareConditions($conditions).' LIMIT 1', $this->getTableName());
		$results = $wpdb->get_results($sql);
		if (is_array($results) && count($results) > 0) {
			return $results[0];
		}

		return null;
	}

	protected function getAllBy(array $conditions, ?array $sort = null, ?int $limit = null, ?int $offset = null): array {
		global $wpdb;

		$conditions = $this->prepareConditions($conditions);

		$sortSQL = $sort ? $wpdb->prepare(" ORDER BY %i ".$sort[1], $sort[0]) : '';
		$limitSQL = $limit ? $wpdb->prepare(" LIMIT %d ", $limit) : '';
		$offsetSQL = $offset ? $wpdb->prepare(" OFFSET %d ", $offset) : '';
		$conditionsSQL = $conditions ? 'WHERE '.$conditions : '';
		$sql = $wpdb->prepare('SELECT * FROM %i '.$conditionsSQL.$sortSQL.$limitSQL.$offsetSQL, $this->getTableName());
		$results = $wpdb->get_results($sql);

		if (is_array($results)) {
			return $results;
		}

		return [];
	}

	abstract protected function getTableName(): string;
	abstract protected function populateData(\stdClass $rawRow): object;

	private function prepareConditions(array $conditions): string {
		global $wpdb;

		$preparedConditions = [];
		foreach ($conditions as $column => $value) {
			if (is_array($value)) {
				$fieldValue = $value[0];
				$fieldValueType = $value[1];
				$operator = count($value) === 3 ? $value[2] : null;

				if (is_array($fieldValue)) {
					$que = implode(',', array_fill(0, count($fieldValue), $fieldValueType));
					$preparedConditions[] = $wpdb->prepare("%i IN (".$que.")", array_merge([$column], $fieldValue));
				} else if ($operator) {
					if ($operator === 'like') {
						$fieldValue = '%'.$wpdb->esc_like($fieldValue).'%';
					}

					$preparedConditions[] = $wpdb->prepare("%i ".$operator." " . $fieldValueType, $column, $fieldValue);
				} else {
					$preparedConditions[] = $wpdb->prepare("%i = " . $fieldValueType, $column, $fieldValue);
				}
			} else {
				$preparedConditions[] = $wpdb->prepare("%i = %s", $column, $value);
			}
		}

		return implode(' AND ', $preparedConditions);
	}

}