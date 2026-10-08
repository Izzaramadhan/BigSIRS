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

export interface RadiologyGroup {
  id: number;
  name: string;
  radiology_category_id: number | null;
  radiology_type_id: number | null;
  activity_type_id: number | null;
  price: number;
  interpretation_price: number;
  loinc_code?: string | null;
  loinc_url?: string | null;
  is_active: boolean;
  
  category?: {
    id: number;
    name: string;
  };
  type?: {
    id: number;
    name: string;
  };
  activity_type?: {
    id: number;
    name: string;
  };
  
  item_groups?: RadiologyItemGroup[];
  radiology_item_group_ids?: number[];
  item_groups_count?: number;
}

export interface RadiologyGroupFilters {
  search?: string;
  category_id?: number | string;
  type_id?: number | string;
}
