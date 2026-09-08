import { Head, useForm } from "@inertiajs/react";
import { AuthenticatedLayout } from "@/Components/layout";
import { InvestorForm } from "./InvestorForm";
import { UserPlus } from "lucide-react";

export default function InvestorCreate() {
    const { data, setData, post, processing, errors } = useForm({
        name: "",
        reference: "",
        mobile: "",
        address: "",
        deed_ratio: "100",
        start_profit_month: "",
        end_profit_month: "",
        status: "active",
    });

    return (
        <AuthenticatedLayout title="New Investor">
            <Head title="New Investor" />
            <InvestorForm
                mode="create"
                data={data}
                setData={setData}
                submit={(url) => post(url, { preserveScroll: true })}
                processing={processing}
                errors={errors}
            />
        </AuthenticatedLayout>
    );
}
