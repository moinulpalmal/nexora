import {
    Callout,
    Field,
    Fields,
    Guide,
    GuideSection,
    Para,
    RelatedGuide,
    Shot,
    Step,
    Steps,
    Ui,
} from '@/components/training/vocabulary';

/**
 * Admin → Enums (`admin.enums.index`) — the read-only catalogue of enums.
 * Source: documentation/admin.md §3.4.
 */
export default function EnumsGuide() {
    return (
        <Guide>
            <GuideSection kind="purpose">
                <Para
                    en="Every fixed list of choices the application uses — a record's status, a project's stage, an import's state — with what each list is for, the choices it allows, and the value saved for each. The screen is for reading only. These lists are part of the application itself; nobody adds or renames a choice here."
                    bn="অ্যাপ্লিকেশন যে প্রতিটি নির্দিষ্ট পছন্দের তালিকা ব্যবহার করে — কোনো রেকর্ডের স্ট্যাটাস, একটি প্রজেক্টের ধাপ, একটি ইমপোর্টের অবস্থা — প্রতিটি তালিকা কীসের জন্য, এতে কোন কোন পছন্দ আছে, এবং প্রতিটির জন্য কোন মান সংরক্ষিত হয়। স্ক্রিনটি শুধু পড়ার জন্য। এই তালিকাগুলো অ্যাপ্লিকেশনেরই অংশ; এখানে কেউ কোনো পছন্দ যোগ করেন না বা নাম বদলান না।"
                />
            </GuideSection>

            <GuideSection kind="who">
                <Para
                    en={
                        <>
                            <Ui>admin.enums.view</Ui>. The admin and viewer
                            roles hold it.
                        </>
                    }
                    bn={
                        <>
                            <Ui>admin.enums.view</Ui>। admin ও viewer রোলে এটি
                            আছে।
                        </>
                    }
                />
            </GuideSection>

            <GuideSection kind="steps">
                <Steps>
                    <Step
                        en={
                            <>
                                Open Admin → Enums. The lists are grouped by the
                                module that owns them, one card each.
                            </>
                        }
                        bn={
                            <>
                                Admin → Enums খুলুন। তালিকাগুলো যে মডিউলের, সেই
                                অনুযায়ী সাজানো — প্রতিটির জন্য একটি কার্ড।
                            </>
                        }
                    >
                        <Shot
                            id="overview"
                            en="Each enum with its purpose and a table of its choices."
                            bn="প্রতিটি এনাম — এর উদ্দেশ্য এবং এর পছন্দগুলোর একটি টেবিল।"
                        />
                    </Step>
                    <Step
                        en={
                            <>
                                To find one, type in the search box — an enum's
                                name, a choice such as <Ui>On Hold</Ui>, or a
                                saved value such as <Ui>on-hold</Ui> — or pick a
                                module in the <Ui>All modules</Ui> filter.
                            </>
                        }
                        bn={
                            <>
                                কোনোটি খুঁজতে সার্চ বক্সে লিখুন — একটি এনামের
                                নাম, একটি পছন্দ যেমন <Ui>On Hold</Ui>, অথবা
                                সংরক্ষিত একটি মান যেমন <Ui>on-hold</Ui> — অথবা{' '}
                                <Ui>All modules</Ui> ফিল্টারে একটি মডিউল বেছে
                                নিন।
                            </>
                        }
                    />
                </Steps>
            </GuideSection>

            <GuideSection kind="fields">
                <Fields>
                    <Field
                        label="Case"
                        en="The choice's name inside the application."
                        bn="অ্যাপ্লিকেশনের ভেতরে পছন্দটির নাম।"
                    />
                    <Field
                        label="Stored value"
                        en="What is saved in the database for that choice. This is what you see in the audit log and in exported data."
                        bn="ওই পছন্দের জন্য ডাটাবেসে যা সংরক্ষিত হয়। অডিট লগ ও এক্সপোর্ট করা ডেটায় আপনি এটিই দেখবেন।"
                    />
                    <Field
                        label="Label"
                        en="The words people see for it on screens. A list nobody picks from on screen has no label column."
                        bn="স্ক্রিনে মানুষ এর জন্য যে শব্দগুলো দেখেন। যে তালিকা থেকে স্ক্রিনে কেউ বেছে নেন না, তাতে লেবেলের কলাম থাকে না।"
                    />
                </Fields>
            </GuideSection>

            <GuideSection kind="mistakes">
                <Callout
                    tone="info"
                    en="An audit entry or an export shows a short code, such as A or on-hold, where you expected a word? Search for it here to see which choice it is."
                    bn="অডিট এন্ট্রি বা এক্সপোর্টে কোনো শব্দের জায়গায় একটি ছোট কোড দেখছেন, যেমন A বা on-hold? এটি কোন পছন্দ তা জানতে এখানে খুঁজুন।"
                />
            </GuideSection>

            <GuideSection kind="related">
                <RelatedGuide
                    to="admin.permissions"
                    en="The other catalogue defined in code: permissions."
                    bn="কোডে সংজ্ঞায়িত আরেকটি তালিকা: পারমিশন।"
                />
            </GuideSection>
        </Guide>
    );
}
