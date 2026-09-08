import { Head, useForm } from "@inertiajs/react";
import { AuthenticatedLayout } from "@/Components/layout";
import { InvestorForm } from "./InvestorForm";
import { Pencil } from "lucide-react";

interface EditProps {
    investor: {
        id: number;
        name: string;
        reference: string | null;
        mobile: string | null;
        address: string | null;
        deed_ratio: string;
        status: string;
        start_profit_month: string | null;
        end_profit_month: string | null;
    };
}

export default function InvestorEdit({ investor }: EditProps) {
    const { data, setData, put, processing, errors } = useForm({
        name: investor.name,
        reference: investor.reference ?? "",
        mobile: investor.mobile ?? "",
        address: investor.address ?? "",
        deed_ratio: investor.deed_ratio,
        start_profit_month: investor.start_profit_month ?? "",
        end_profit_month: investor.end_profit_month ?? "",
        status: investor.status,
    });

    return (
        <AuthenticatedLayout title={`Edit ${investor.name}`}>
            <Head title={`Edit ${investor.name}`} />
            <InvestorForm
                mode="edit"
                investorId={investor.id}
                data={data}
                setData={setData}
                submit={(url) => put(url, { preserveScroll: true })}
                processing={processing}
                errors={errors}
            />
        </AuthenticatedLayout>
    );
}
