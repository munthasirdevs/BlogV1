import { useState } from 'react';
import { cn } from '@/utils';
import { Button, Badge } from '@/components/atoms';
import { useCategories, useTags } from '@/hooks';
import { Filter, X, ChevronDown, ChevronUp } from 'lucide-react';

/**
 * FilterSidebar component - Category and tag filters for blog list
 * @param {Object} props - Component props
 * @param {Object} props.filters - Current filter state
 * @param {Function} props.onFilterChange - Filter change handler
 * @param {Function} props.onClearFilters - Clear all filters handler
 * @param {boolean} props.isMobile - Mobile view (default: false)
 * @param {Function} props.onClose - Close handler for mobile
 */
function FilterSidebar({ filters = {}, onFilterChange, onClearFilters, isMobile = false, onClose }) {
  const [expandedSections, setExpandedSections] = useState({
    categories: true,
    tags: true,
  });

  const { data: categoriesData } = useCategories();
  const { data: tagsData } = useTags();

  const categories = categoriesData?.data || [];
  const tags = tagsData?.data || [];

  const selectedCategories = filters.categories || [];
  const selectedTags = filters.tags || [];

  const toggleSection = (section) => {
    setExpandedSections((prev) => ({
      ...prev,
      [section]: !prev[section],
    }));
  };

  const handleCategoryToggle = (categoryId) => {
    const newCategories = selectedCategories.includes(categoryId)
      ? selectedCategories.filter((id) => id !== categoryId)
      : [...selectedCategories, categoryId];
    onFilterChange({ ...filters, categories: newCategories });
  };

  const handleTagToggle = (tagId) => {
    const newTags = selectedTags.includes(tagId)
      ? selectedTags.filter((id) => id !== tagId)
      : [...selectedTags, tagId];
    onFilterChange({ ...filters, tags: newTags });
  };

  const hasActiveFilters = selectedCategories.length > 0 || selectedTags.length > 0;

  const sidebarContent = (
    <div className={cn('space-y-6', isMobile && 'p-4')}>
      {/* Header */}
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-2">
          <Filter className="w-5 h-5 text-secondary-500" />
          <h3 className="text-lg font-semibold text-secondary-900 dark:text-secondary-100">
            Filters
          </h3>
        </div>
        {isMobile && (
          <button
            onClick={onClose}
            className="p-2 rounded-lg hover:bg-secondary-100 dark:hover:bg-secondary-800"
            aria-label="Close filters"
          >
            <X className="w-5 h-5" />
          </button>
        )}
      </div>

      {/* Active filters */}
      {hasActiveFilters && (
        <div className="flex flex-wrap gap-2 pb-4 border-b border-secondary-200 dark:border-secondary-700">
          <span className="text-sm text-secondary-500 dark:text-secondary-400 self-center">
            Active:
          </span>
          {selectedCategories.map((categoryId) => {
            const category = categories.find((c) => c.id === categoryId);
            return (
              category && (
                <Badge
                  key={categoryId}
                  variant="primary"
                  className="cursor-pointer"
                  onClick={() => handleCategoryToggle(categoryId)}
                >
                  {category.name}
                  <X className="w-3 h-3 ml-1" />
                </Badge>
              )
            );
          })}
          {selectedTags.map((tagId) => {
            const tag = tags.find((t) => t.id === tagId);
            return (
              tag && (
                <Badge
                  key={tagId}
                  variant="secondary"
                  className="cursor-pointer"
                  onClick={() => handleTagToggle(tagId)}
                >
                  {tag.name}
                  <X className="w-3 h-3 ml-1" />
                </Badge>
              )
            );
          })}
          <Button variant="ghost" size="sm" onClick={onClearFilters} className="ml-auto">
            Clear all
          </Button>
        </div>
      )}

      {/* Categories */}
      <div>
        <button
          onClick={() => toggleSection('categories')}
          className="flex items-center justify-between w-full py-2 text-left"
        >
          <h4 className="text-sm font-semibold text-secondary-700 dark:text-secondary-300">
            Categories
          </h4>
          {expandedSections.categories ? (
            <ChevronUp className="w-4 h-4 text-secondary-500" />
          ) : (
            <ChevronDown className="w-4 h-4 text-secondary-500" />
          )}
        </button>

        {expandedSections.categories && (
          <div className="mt-2 space-y-2">
            {categories.map((category) => (
              <label
                key={category.id}
                className="flex items-center gap-3 cursor-pointer group"
              >
                <input
                  type="checkbox"
                  checked={selectedCategories.includes(category.id)}
                  onChange={() => handleCategoryToggle(category.id)}
                  className="w-4 h-4 rounded border-secondary-300 dark:border-secondary-600 text-primary-600 focus:ring-primary-500"
                />
                <span className="text-sm text-secondary-600 dark:text-secondary-400 group-hover:text-secondary-900 dark:group-hover:text-secondary-200 transition-colors">
                  {category.name}
                </span>
                {category.post_count !== undefined && (
                  <span className="text-xs text-secondary-400 dark:text-secondary-500 ml-auto">
                    ({category.post_count})
                  </span>
                )}
              </label>
            ))}
          </div>
        )}
      </div>

      {/* Tags */}
      <div>
        <button
          onClick={() => toggleSection('tags')}
          className="flex items-center justify-between w-full py-2 text-left"
        >
          <h4 className="text-sm font-semibold text-secondary-700 dark:text-secondary-300">
            Tags
          </h4>
          {expandedSections.tags ? (
            <ChevronUp className="w-4 h-4 text-secondary-500" />
          ) : (
            <ChevronDown className="w-4 h-4 text-secondary-500" />
          )}
        </button>

        {expandedSections.tags && (
          <div className="mt-2 flex flex-wrap gap-2">
            {tags.map((tag) => (
              <Badge
                key={tag.id}
                variant={selectedTags.includes(tag.id) ? 'primary' : 'secondary'}
                className={cn(
                  'cursor-pointer transition-all',
                  selectedTags.includes(tag.id)
                    ? 'ring-2 ring-primary-500'
                    : 'hover:bg-secondary-200 dark:hover:bg-secondary-700'
                )}
                onClick={() => handleTagToggle(tag.id)}
              >
                {tag.name}
              </Badge>
            ))}
          </div>
        )}
      </div>
    </div>
  );

  if (isMobile) {
    return (
      <div className={cn(
        'fixed inset-0 z-50 bg-white dark:bg-secondary-900 overflow-y-auto',
        'animate-in slide-in-from-right duration-300'
      )}>
        {sidebarContent}
      </div>
    );
  }

  return (
    <aside className={cn(
      'sticky top-24',
      'bg-white dark:bg-secondary-800 rounded-xl p-4',
      'border border-secondary-200 dark:border-secondary-700',
      'shadow-sm'
    )}>
      {sidebarContent}
    </aside>
  );
}

export default FilterSidebar;
