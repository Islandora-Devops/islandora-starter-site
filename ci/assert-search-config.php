<?php

declare(strict_types=1);

use Drupal\block\Entity\Block;
use Drupal\views\Entity\View;

$module_handler = \Drupal::moduleHandler();

$required_modules = [
  'advanced_search',
  'facets_exposed_filters',
  'facets_searchbox_widget',
  'views_filters_summary',
  'views_filters_summary_a11y',
];

foreach ($required_modules as $module) {
  if (!$module_handler->moduleExists($module)) {
    throw new RuntimeException(sprintf('Required module %s is not enabled.', $module));
  }
}

if ($module_handler->moduleExists('facets_summary')) {
  throw new RuntimeException('The legacy Facets Summary submodule must remain disabled.');
}

$view = View::load('solr_search_content');
if (!$view) {
  throw new RuntimeException('The solr_search_content View is missing.');
}

$dependencies = $view->getDependencies()['module'] ?? [];
foreach ([
  'advanced_search',
  'facets_exposed_filters',
  'facets_searchbox_widget',
  'views_filters_summary',
] as $dependency) {
  if (!in_array($dependency, $dependencies, TRUE)) {
    throw new RuntimeException(sprintf(
      'The search View does not declare its %s module dependency.',
      $dependency,
    ));
  }
}

$expected_filters = [
  'facets_field_resource_type',
  'facets_member_of_title',
  'facets_field_linked_agent_name',
  'facets_subject_general_name',
  'facets_edtf_year',
  'facets_field_physical_form',
  'facets_field_publisher',
  'facets_subject_names_name',
  'facets_subject_temporal_name',
];

foreach (['default', 'block_1', 'page_1'] as $display_id) {
  $display = $view->getDisplay($display_id);
  $summary = $display['display_options']['header']['views_filters_summary'] ?? NULL;

  if (!is_array($summary)) {
    throw new RuntimeException(sprintf(
      'Display %s is missing its Views Filters Summary header.',
      $display_id,
    ));
  }

  $expected_options = [
    'plugin_id' => 'views_filters_summary',
    'empty' => TRUE,
    'content' => '@exposed_filter_summary',
    'filters' => $expected_filters,
    'show_labels' => TRUE,
    'show_remove_link' => TRUE,
    'show_reset_link' => TRUE,
    'group_values' => TRUE,
    'filters_reset_link_title' => 'Clear all filters',
    'filters_summary_separator' => '',
    'filters_summary_prefix' => 'Selected filters:',
  ];

  foreach ($expected_options as $key => $expected_value) {
    if (($summary[$key] ?? NULL) !== $expected_value) {
      throw new RuntimeException(sprintf(
        'Display %s has an invalid Views Filters Summary %s option.',
        $display_id,
        $key,
      ));
    }
  }
}

$expected_fields = [
  'title_aggregated_fulltext',
  'abstract_description_fulltext',
  'linked_agent_name_fulltext',
  'field_publisher_fulltext',
];

$integrated_displays = [
  'block_1' => 'field_member_of',
  'page_1' => '',
];

foreach ($integrated_displays as $display_id => $context_filter) {
  $display = $view->getDisplay($display_id);
  $display_options = $display['display_options'] ?? [];
  $search_form = $display_options['header']['advanced_search_form'] ?? NULL;
  $toolbar = $display_options['header']['advanced_search_results_toolbar'] ?? NULL;

  if (!is_array($search_form) || ($search_form['plugin_id'] ?? NULL) !== 'advanced_search_form') {
    throw new RuntimeException(sprintf(
      'Display %s is missing its integrated Advanced Search form header area.',
      $display_id,
    ));
  }

  if (($search_form['fields'] ?? NULL) !== $expected_fields) {
    throw new RuntimeException(sprintf(
      'Display %s does not expose the expected Advanced Search fields.',
      $display_id,
    ));
  }

  if (($search_form['context_filter'] ?? NULL) !== $context_filter) {
    throw new RuntimeException(sprintf(
      'Display %s has an invalid Advanced Search context filter.',
      $display_id,
    ));
  }

  if (!is_array($toolbar) || ($toolbar['plugin_id'] ?? NULL) !== 'advanced_search_results_toolbar') {
    throw new RuntimeException(sprintf(
      'Display %s is missing its integrated search results toolbar header area.',
      $display_id,
    ));
  }

  foreach ([
    'override_list_on_off' => TRUE,
    'override_grid_on_off' => TRUE,
    'override-default-display-mode' => 'list',
  ] as $key => $expected_value) {
    if (($toolbar[$key] ?? NULL) !== $expected_value) {
      throw new RuntimeException(sprintf(
        'Display %s has an invalid search results toolbar %s option.',
        $display_id,
        $key,
      ));
    }
  }

  if (($display_options['exposed_block'] ?? NULL) !== TRUE) {
    throw new RuntimeException(sprintf(
      'Display %s must expose its filters as a Primary-region block.',
      $display_id,
    ));
  }
}

$default_display = $view->getDisplay('default');
$default_options = $default_display['display_options'] ?? [];
$bef_filters = $default_options['exposed_form']['options']['bef']['filter'] ?? [];
$view_filters = $default_options['filters'] ?? [];
$pager = $default_options['pager'] ?? [];

if (($pager['type'] ?? NULL) !== 'mini'
  || ($pager['options']['items_per_page'] ?? NULL) !== 15
  || ($pager['options']['expose']['items_per_page'] ?? NULL) !== TRUE
  || ($pager['options']['expose']['items_per_page_options'] ?? NULL) !== '15,60,120') {
  throw new RuntimeException(
    'Search results must retain the native 15-item Views pager and page-size options.',
  );
}

if (($default_options['use_ajax'] ?? NULL) !== TRUE) {
  throw new RuntimeException('Search results must retain Views AJAX paging.');
}

foreach (array_keys($integrated_displays) as $display_id) {
  $display_options = $view->getDisplay($display_id)['display_options'] ?? [];
  if (isset($display_options['pager'])
    || (($display_options['defaults']['pager'] ?? TRUE) !== TRUE)) {
    throw new RuntimeException(sprintf(
      'Display %s must inherit the native bottom pager from the default display.',
      $display_id,
    ));
  }
}

$style_contracts = [
  'default' => [
    'type' => 'views_bootstrap_list_group',
    'options' => [
      'row_class' => 'islandora-search-result',
      'list_group_class_custom' => 'islandora-search-results',
    ],
  ],
  'block_1' => [
    'type' => 'views_bootstrap_grid',
    'options' => [
      'row_class' => 'islandora-card-column',
      'grid_class' => 'g-4 islandora-collection-members',
    ],
  ],
];

foreach ($style_contracts as $display_id => $expected_style) {
  $display = $view->getDisplay($display_id);
  $style = $display['display_options']['style'] ?? [];
  if (($style['type'] ?? NULL) !== $expected_style['type']) {
    throw new RuntimeException(sprintf(
      'Display %s must retain the %s style required by its List/Grid layouts.',
      $display_id,
      $expected_style['type'],
    ));
  }

  foreach ($expected_style['options'] as $key => $expected_value) {
    if (($style['options'][$key] ?? NULL) !== $expected_value) {
      throw new RuntimeException(sprintf(
        'Display %s must retain its List/Grid %s class contract.',
        $display_id,
        $key,
      ));
    }
  }
}

$searchable_facets = [
  'facets_member_of_title',
  'facets_field_linked_agent_name',
  'facets_subject_general_name',
];

foreach ($searchable_facets as $filter_id) {
  if (($bef_filters[$filter_id]['plugin_id'] ?? NULL) !== 'facets_searchbox') {
    throw new RuntimeException(sprintf(
      'Facet filter %s must use the searchable Facets widget.',
      $filter_id,
    ));
  }

  if (($view_filters[$filter_id]['facet']['hard_limit'] ?? NULL) !== 100) {
    throw new RuntimeException(sprintf(
      'Facet filter %s must return up to 100 searchable values.',
      $filter_id,
    ));
  }
}

$year_widget = $bef_filters['facets_edtf_year'] ?? [];
if (($year_widget['plugin_id'] ?? NULL) !== 'facets_exposed_range_slider') {
  throw new RuntimeException('The year facet must retain its exposed range slider.');
}

if (($year_widget['step'] ?? NULL) !== '1' || ($year_widget['snap_to_values'] ?? NULL) !== FALSE) {
  throw new RuntimeException('The year range slider must retain one-year continuous steps.');
}

$expected_blocks = [
  'islandora_dxpr_collection_filters' => [
    'plugin' => 'views_exposed_filter_block:solr_search_content-block_1',
    'region' => 'sidebar_first',
  ],
  'islandora_dxpr_search_filters' => [
    'plugin' => 'views_exposed_filter_block:solr_search_content-page_1',
    'region' => 'sidebar_first',
  ],
  'islandora_dxpr_views_block__solr_search_content_block_1' => [
    'plugin' => 'views_block:solr_search_content-block_1',
    'region' => 'content',
  ],
];

foreach ($expected_blocks as $block_id => $expected) {
  $block = Block::load($block_id);
  if (!$block
    || !$block->status()
    || $block->getPluginId() !== $expected['plugin']
    || $block->getTheme() !== 'islandora_dxpr'
    || $block->getRegion() !== $expected['region']) {
    throw new RuntimeException(sprintf(
      'Search block %s must be enabled in the Islandora DXPR %s region.',
      $block_id,
      $expected['region'],
    ));
  }

  $visibility = $block->getVisibility();
  if ($block_id === 'islandora_dxpr_search_filters') {
    $request_path = $visibility['request_path'] ?? [];
    if (($request_path['negate'] ?? NULL) !== FALSE
      || ($request_path['pages'] ?? NULL) !== '/search') {
      throw new RuntimeException('The repository facet block must be limited to /search.');
    }
  }
  else {
    $context = $visibility['context_all'] ?? [];
    if (($context['values'] ?? NULL) !== 'collection') {
      throw new RuntimeException(sprintf(
        'Search block %s must be limited to the Collection context.',
        $block_id,
      ));
    }
  }
}

$search_control_blocks = [];
$search_plugin_prefixes = [
  'advanced_search_block:solr_search_content',
  'advanced_search_result_pager:solr_search_content',
  'views_exposed_filter_block:solr_search_content',
  'views_block:solr_search_content',
];

foreach (Block::loadMultiple() as $block) {
  if ($block->getTheme() !== 'islandora_dxpr') {
    continue;
  }

  $plugin_id = $block->getPluginId();
  foreach ($search_plugin_prefixes as $prefix) {
    if (str_starts_with($plugin_id, $prefix)) {
      $search_control_blocks[$block->id()] = $plugin_id;
      break;
    }
  }
}

$expected_search_control_blocks = array_map(
  static fn (array $expected): string => $expected['plugin'],
  $expected_blocks,
);
ksort($search_control_blocks);
ksort($expected_search_control_blocks);

if ($search_control_blocks !== $expected_search_control_blocks) {
  throw new RuntimeException(sprintf(
    'Only the two Primary facet blocks and collection-members content block may remain; found: %s.',
    json_encode($search_control_blocks, JSON_THROW_ON_ERROR),
  ));
}

print "Integrated search configuration is valid.\n";
