export type LandingPlan = {
    name: string;
    display_name: string;
    description: string | null;
    price_monthly: number;
    max_branches: number;
    features: string[];
};
