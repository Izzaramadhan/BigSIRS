export interface RadiologyItemGroup {
  id: number;
  name: string;
  description?: string;
  is_active: boolean;
}

export interface RadiologyItem {
  id: number;
  name: string;
  radiology_item_group_id: number | null;
  is_active: boolean;
  group?: {
    id: number;
    name: string;
  };
}

export interface RadiologyItemFilters {
  search?: string;
  radiology_item_group_id?: number | string;
}
